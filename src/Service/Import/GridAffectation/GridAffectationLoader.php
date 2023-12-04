<?php

namespace App\Service\Import\GridAffectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\EventListener\UserCreatedListener;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\ManagerInterface;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Service\Import\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GridAffectationLoader
{
    private const FLUSH_COUNT = 250;

    private array $metadata = [
        'nb_users_created' => 0,
        'nb_partners' => 0,
        'errors' => [],
    ];

    public function __construct(
        private CsvParser $csvParser,
        private PartnerFactory $partnerFactory,
        private PartnerManager $partnerManager,
        private UserFactory $userFactory,
        private UserManager $userManager,
        private ManagerInterface $manager,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private UserCreatedListener $userAddedSubscriber,
    ) {
    }

    public function validate(array $data, bool $isModeUpdate = false): array
    {
        $errors = [];
        $mailPartners = [];
        $mailUsers = [];

        $emailConstraint = new Email(['mode' => 'strict'], true);
        $numLine = 1;

        foreach ($data as $item) {
            ++$numLine;
            if (\count($item) > 1) {
                if (empty($item[GridAffectationHeader::PARTNER_NAME_INSTITUTION])) {
                    $errors[] = sprintf('line %d : Nom de partenaire manquant', $numLine);
                }
                if (!\in_array($item[GridAffectationHeader::PARTNER_TYPE], PartnerType::getLabelList())) {
                    $errors[] = sprintf(
                        'line %d : Type incorrect pour %s --> %s',
                        $numLine,
                        $item[GridAffectationHeader::PARTNER_NAME_INSTITUTION],
                        $item[GridAffectationHeader::PARTNER_TYPE]
                    );
                }
                // if partner has an email, it should be valid and not existing for another partner
                $emailPartner = trim($item[GridAffectationHeader::PARTNER_EMAIL]);
                if (!empty($emailPartner)) {
                    $violations = $this->validator->validate($emailPartner, $emailConstraint);
                    if (\count($violations) > 0) {
                        $errors[] = sprintf('line %d : Email incorrect pour un partenaire : %s',
                            $numLine,
                            $emailPartner
                        );
                    }

                    if (!$isModeUpdate) {
                        /** @var Partner $partnerToCreate */
                        $partnerToCreate = $this->partnerManager->findOneBy(['email' => $emailPartner]);
                        if (null !== $partnerToCreate) {
                            $errors[] = sprintf(
                                'line %d : Partenaire déjà existant avec (%s) dans %s, nom : %s',
                                $numLine,
                                $emailPartner,
                                $partnerToCreate->getTerritory()->getName(),
                                $partnerToCreate->getNom()
                            );
                        }
                    }

                    // store partner mail to check duplicates
                    if (!empty($item[GridAffectationHeader::PARTNER_EMAIL])) {
                        $mailPartners[$item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]] =
                            $item[GridAffectationHeader::PARTNER_EMAIL];
                    }
                }

                $emailUser = trim($item[GridAffectationHeader::USER_EMAIL]);
                if (empty($emailUser) && !empty($item[GridAffectationHeader::USER_ROLE])) {
                    $errors[] = sprintf(
                        'line %d : Email manquant pour %s %s, partenaire %s',
                        $numLine,
                        $item[GridAffectationHeader::USER_FIRSTNAME],
                        $item[GridAffectationHeader::USER_LASTNAME],
                        $item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]
                    );
                } else {
                    // email must be valid and not used by another user of another partner
                    $violations = $this->validator->validate($emailUser, $emailConstraint);
                    if (\count($violations) > 0) {
                        $errors[] = sprintf('line %d : Email incorrect pour un utilisateur : %s', $numLine, $emailUser);
                    }

                    /** @var User $userToCreate */
                    $userToCreate = $this->userManager->findOneBy(['email' => $emailUser]);
                    if (!$isModeUpdate
                        && null !== $userToCreate
                        && !\in_array('ROLE_USAGER', $userToCreate->getRoles())
                    ) {
                        $errors[] = sprintf(
                            'line %d : Utilisateur déjà existant avec (%s) dans %s, partenaire : %s, rôle : %s',
                            $numLine,
                            $emailUser,
                            $userToCreate->getTerritory()->getName(),
                            $userToCreate->getPartner()->getNom(),
                            $userToCreate->getRoleLabel()
                        );
                    }
                    // store user mail to check duplicates
                    if (!empty($item[GridAffectationHeader::USER_EMAIL])) {
                        $mailUsers[] = $item[GridAffectationHeader::USER_EMAIL];
                    }
                }
                if (!empty($item[GridAffectationHeader::USER_ROLE])
                    && !\in_array($item[GridAffectationHeader::USER_ROLE], array_keys(User::ROLES))) {
                    $errors[] = sprintf(
                        'line %d : Rôle incorrect pour %s --> %s',
                        $numLine,
                        $item[GridAffectationHeader::USER_EMAIL],
                        $item[GridAffectationHeader::USER_ROLE]
                    );
                }
            }
        }

        // check if there are no duplicate email between partners
        $duplicatesMailPartners = $this->checkIfDuplicates($mailPartners);
        if (\count($duplicatesMailPartners) > 0) {
            $errors[] = 'Certains partenaires ont un mail en commun '.implode(',', array_keys($duplicatesMailPartners));
        }

        // check if there are no duplicate email between users
        $duplicatesMailUsers = $this->checkIfDuplicates($mailUsers);
        if (\count($duplicatesMailUsers) > 0) {
            $errors[] = 'Certains utilisateurs ont un mail en commun '.implode(',', array_keys($duplicatesMailUsers));
        }

        // check if there are no duplicate email between partners and users
        $mails = array_merge($mailPartners, $mailUsers);
        $duplicatesMails = $this->checkIfDuplicates($mails);
        if (\count($duplicatesMails) > 0) {
            $errors[] = 'Certains utilisateurs ont un mail en commun avec un partenaire '
                .implode(',', array_keys($duplicatesMails));
        }

        return $errors;
    }

    public function load(Territory $territory, array $data, array $ignoreNotifPartnerTypes): void
    {
        $countUsers = 0;
        $partner = null;
        $newPartnerNames = [];
        foreach ($data as $item) {
            if (\count($item) > 1) {
                $partnerName = trim($item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]);
                $partnerType = PartnerType::tryFromLabel($item[GridAffectationHeader::PARTNER_TYPE]);

                if (!\in_array($partnerName, $newPartnerNames)) {
                    $partner = $this->partnerFactory->createInstanceFrom(
                        territory: $territory,
                        name: $partnerName,
                        email: !empty($item[GridAffectationHeader::PARTNER_EMAIL])
                            ? trim($item[GridAffectationHeader::PARTNER_EMAIL])
                            : null,
                        type: $partnerType,
                        insee: $item[GridAffectationHeader::PARTNER_CODE_INSEE]
                    );
                    /** @var ConstraintViolationList $errors */
                    $errors = $this->validator->validate($partner);
                    if (0 === $errors->count() && !$this->partnerAlreadyExists($partner)) {
                        $this->partnerManager->save($partner, false);
                        ++$this->metadata['nb_partners'];
                        $newPartnerNames[] = $partnerName;
                    } elseif ($errors->count() > 0) {
                        $this->metadata['errors'][] = sprintf('%s', (string) $errors);
                        continue;
                    }
                }

                $roleLabel = $item[GridAffectationHeader::USER_ROLE];
                $email = trim($item[GridAffectationHeader::USER_EMAIL]);
                if (!empty($roleLabel) && !empty($email)) {
                    ++$countUsers;
                    $user = $this->userFactory->createInstanceFrom(
                        roleLabel: $roleLabel,
                        territory: $territory,
                        partner: $partner,
                        firstname: trim($item[GridAffectationHeader::USER_FIRSTNAME]),
                        lastname: trim($item[GridAffectationHeader::USER_LASTNAME]),
                        email: $email,
                        isActivateAccountNotificationEnabled: !\in_array($partnerType->name, $ignoreNotifPartnerTypes)
                    );

                    /** @var ConstraintViolationList $errors */
                    $errors = $this->validator->validate($user);
                    if (0 === $errors->count()) {
                        $this->userManager->save($user, false);
                        ++$this->metadata['nb_users_created'];
                    } else {
                        $this->metadata['errors'][] = sprintf('line : %s', (string) $errors);
                    }
                }

                if (0 === $countUsers % self::FLUSH_COUNT) {
                    $this->logger->info(sprintf('in progress - %s users created or updated', $countUsers));
                    $this->manager->flush();
                }
            }
        }

        $this->manager->flush();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function checkIfDuplicates(array $emails): array
    {
        $occurrencesEmails = array_count_values($emails);
        $duplicatesEmails = array_filter($occurrencesEmails, function ($value) {
            return $value > 1;
        });

        return $duplicatesEmails;
    }

    private function partnerAlreadyExists(Partner $partner): bool
    {
        $partners = $this->entityManager->getRepository(Partner::class)->findBy([
            'nom' => $partner->getNom(),
            'territory' => $partner->getTerritory(),
        ]);

        if (0 === \count($partners)) {
            return false;
        }

        return true;
    }
}
