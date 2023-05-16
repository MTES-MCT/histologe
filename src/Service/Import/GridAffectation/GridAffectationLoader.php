<?php

namespace App\Service\Import\GridAffectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\ManagerInterface;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Service\Import\CsvParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GridAffectationLoader
{
    private const FLUSH_COUNT = 250;

    private array $metadata = [
        'nb_users_created' => 0,
        'nb_users_updated' => 0,
        'nb_partners' => 0,
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
    ) {
    }

    public function validate(array $data): array
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
                    $errors[] = 'line '.$numLine.' : Nom de partenaire manquant';
                }
                if (!\in_array($item[GridAffectationHeader::PARTNER_TYPE], PartnerType::getLabelList())) {
                    $errors[] = 'line '.$numLine.' : Type incorrect pour '.$item[GridAffectationHeader::PARTNER_NAME_INSTITUTION].' --> '.$item[GridAffectationHeader::PARTNER_TYPE];
                }
                // if partner has an email, it should be valid and not existing for another partner
                $emailPartner = trim($item[GridAffectationHeader::PARTNER_EMAIL]);
                if (!empty($emailPartner)) {
                    $violations = $this->validator->validate($emailPartner, $emailConstraint);
                    if (\count($violations) > 0) {
                        $errors[] = 'line '.$numLine.' : Email incorrect pour un partenaire : '.$emailPartner;
                    }

                    /** @var Partner $partnerToCreate */
                    $partnerToCreate = $this->partnerManager->findOneBy(['email' => $emailPartner]);
                    if (null !== $partnerToCreate) {
                        $errors[] = 'line '.$numLine.' : Il existe déjà un partenaire avec cette adresse mail : '
                        .$emailPartner.' dans le territoire '
                        .$partnerToCreate->getTerritory()->getName().' avec le nom '.$partnerToCreate->getNom();
                    }
                    // store partner mail to check duplicates
                    $mailPartners[$item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]] = $item[GridAffectationHeader::PARTNER_EMAIL];
                }

                $emailUser = trim($item[GridAffectationHeader::USER_EMAIL]);
                if (empty($emailUser)) {
                    $errors[] = 'line '.$numLine.' : Email manquant pour '.$item[GridAffectationHeader::USER_FIRSTNAME].' '
                    .$item[GridAffectationHeader::USER_LASTNAME].', partenaire '.$item[GridAffectationHeader::PARTNER_NAME_INSTITUTION];
                } else {
                    // email must be valid and not used by another user of another partner
                    $violations = $this->validator->validate($emailUser, $emailConstraint);
                    if (\count($violations) > 0) {
                        $errors[] = 'line '.$numLine.' : Email incorrect pour un utilisateur : '.$emailUser;
                    }

                    /** @var User $userToCreate */
                    $userToCreate = $this->userManager->findOneBy(['email' => $emailUser]);

                    if (null !== $userToCreate && !\in_array('ROLE_USAGER', $userToCreate->getRoles())) {
                        $errors[] = 'line '.$numLine.' : Il existe déjà un utilisateur avec cette adresse mail : '
                        .$emailUser.' dans le territoire '.$userToCreate->getTerritory()->getName()
                        .' et dans le partenaire '.$userToCreate->getPartner()->getNom()
                        .' avec le rôle '.$userToCreate->getRoleLabel();
                    }
                    // store user mail to check duplicates
                    $mailUsers[] = $item[GridAffectationHeader::USER_EMAIL];
                }
                if (!\in_array($item[GridAffectationHeader::USER_ROLE], array_keys(User::ROLES))) {
                    $errors[] = 'line '.$numLine.' : Rôle incorrect pour '.$item[GridAffectationHeader::USER_EMAIL].' --> '.$item[GridAffectationHeader::USER_ROLE];
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
            $errors[] = 'Certains utilisateurs ont un mail en commun avec un partenaire '.implode(',', array_keys($duplicatesMails));
        }

        return $errors;
    }

    public function load(Territory $territory, array $data): void
    {
        // TODO LATER : command should be usable several times (update partners and users)
        $countUsers = 0;
        $partner = null;
        $user = null;
        $userToCreate = null;
        $newPartnerName = [];
        foreach ($data as $item) {
            if (\count($item) > 1) {
                $partnerName = trim($item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]);

                if (!\in_array($partnerName, $newPartnerName)) {
                    $partner = $this->partnerFactory->createInstanceFrom(
                        territory: $territory,
                        name: $partnerName,
                        email: !empty($item[GridAffectationHeader::PARTNER_EMAIL]) ? trim($item[GridAffectationHeader::PARTNER_EMAIL]) : null,
                        type: PartnerType::tryFromLabel($item[GridAffectationHeader::PARTNER_TYPE]),
                        insee: $item[GridAffectationHeader::PARTNER_CODE_INSEE]
                    );
                    $this->partnerManager->save($partner, false);
                    ++$this->metadata['nb_partners'];
                    $newPartnerName[] = $partnerName;
                }

                $roleLabel = $item[GridAffectationHeader::USER_ROLE];
                $email = trim($item[GridAffectationHeader::USER_EMAIL]);
                if (!empty($roleLabel) && !empty($email)) {
                    ++$countUsers;
                    /** @var User $userToCreate */
                    $userToCreate = $this->userManager->findOneBy(['email' => $email]);
                    if (null !== $userToCreate && \in_array('ROLE_USAGER', $userToCreate->getRoles())) {
                        $data = [];
                        $data['nom'] = $userToCreate->getNom();
                        $data['prenom'] = $userToCreate->getPrenom();
                        $data['roles'] = \in_array($roleLabel, User::ROLES) ? [$roleLabel] : [User::ROLES[$roleLabel]];
                        $data['email'] = $userToCreate->getEmail();
                        $data['isMailingActive'] = true;
                        $data['territory'] = $territory;
                        $data['partner'] = $partner;
                        $data['statut'] = User::STATUS_INACTIVE;
                        $this->userManager->updateUserFromData($userToCreate, $data);
                        ++$this->metadata['nb_users_updated'];
                    } else {
                        $user = $this->userFactory->createInstanceFrom(
                            roleLabel: $roleLabel,
                            territory: $territory,
                            partner: $partner,
                            firstname: trim($item[GridAffectationHeader::USER_FIRSTNAME]),
                            lastname: trim($item[GridAffectationHeader::USER_LASTNAME]),
                            email: $email
                        );

                        $this->throwException($user);
                        $this->userManager->save($user, false);
                        ++$this->metadata['nb_users_created'];
                    }

                    if (0 === $countUsers % self::FLUSH_COUNT) {
                        $this->logger->info(sprintf('in progress - %s users created or updated', $countUsers));
                        $this->manager->flush();
                    }
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

    private function throwException(User $user): void
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \Exception(sprintf('%s (%s)', $error->getMessage(), $user->getEmail()));
            }
        }
    }
}
