<?php

namespace App\Service\Import\GridAffectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\ManagerInterface;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Utils\TrimHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GridAffectationLoader
{
    private const int FLUSH_COUNT = 250;

    /**
     * @var array{
     *     nb_users_created: int,
     *     nb_users_multi_territory: int,
     *     nb_partners: int,
     *     errors: string[]
     * }
     */
    private array $metadata = [
        'nb_users_created' => 0,
        'nb_users_multi_territory' => 0,
        'nb_partners' => 0,
        'errors' => [],
    ];

    public function __construct(
        private readonly PartnerFactory $partnerFactory,
        private readonly PartnerManager $partnerManager,
        private readonly UserFactory $userFactory,
        private readonly UserManager $userManager,
        private readonly ManagerInterface $manager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<array<string, mixed>> $data Un tableau de données du fichier CSV à valider
     *
     * @return string[] Liste des messages d'erreur
     */
    public function validate(array $data, Territory $territory, bool $isModeUpdate = false): array
    {
        $errors = [];
        $mailPartners = [];
        $mailUsers = [];

        $emailConstraint = new Email(['mode' => 'strict']);
        $numLine = 1;

        foreach ($data as $item) {
            ++$numLine;
            if (\count($item) > 1) {
                if (empty($item[GridAffectationHeader::PARTNER_NAME_INSTITUTION])) {
                    $errors[] = \sprintf('line %d : Nom de partenaire manquant', $numLine);
                }
                if (!\in_array($item[GridAffectationHeader::PARTNER_TYPE], PartnerType::getLabelList())) {
                    $errors[] = \sprintf(
                        'line %d : Type incorrect pour %s --> %s',
                        $numLine,
                        $item[GridAffectationHeader::PARTNER_NAME_INSTITUTION],
                        $item[GridAffectationHeader::PARTNER_TYPE]
                    );
                }
                // if partner has an email, it should be valid and not existing in the same territory
                $emailPartner = TrimHelper::safeTrim($item[GridAffectationHeader::PARTNER_EMAIL]);
                if (!empty($emailPartner)) {
                    $violations = $this->validator->validate($emailPartner, $emailConstraint);
                    if (\count($violations) > 0) {
                        $errors[] = \sprintf(
                            'line %d : E-mail incorrect pour un partenaire : %s',
                            $numLine,
                            $emailPartner
                        );
                    }

                    if (!$isModeUpdate) {
                        /** @var Partner $partnerToCheck */
                        $partnerToCheck = $this->partnerManager->findOneBy([
                            'email' => $emailPartner,
                            'isArchive' => false,
                            'territory' => $territory]
                        );
                        if (null !== $partnerToCheck) {
                            $errors[] = \sprintf(
                                'line %d : E-mail partenaire déjà existant dans le territoire avec (%s) dans %s, nom : %s',
                                $numLine,
                                $emailPartner,
                                $partnerToCheck->getTerritory()->getName(),
                                $partnerToCheck->getNom()
                            );
                        }
                    }

                    // store partner mail to check duplicates
                    if (!empty($item[GridAffectationHeader::PARTNER_EMAIL])) {
                        $mailPartners[$item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]] =
                            $item[GridAffectationHeader::PARTNER_EMAIL];
                    }
                }

                $emailUser = TrimHelper::safeTrim($item[GridAffectationHeader::USER_EMAIL]);
                if ($emailUser) {
                    // store user mail to check duplicates
                    $mailUsers[] = $emailUser;
                    $roleLabel = $this->getRoleLabelFromItem($item);
                    if (!$roleLabel) {
                        $errors[] = \sprintf(
                            'line %d : Rôle incorrect pour %s --> %s',
                            $numLine,
                            $item[GridAffectationHeader::USER_EMAIL],
                            $item[GridAffectationHeader::USER_ROLE]
                        );
                    }
                    if ($isModeUpdate) {
                        /** @var UserRepository $userRepository */
                        $userRepository = $this->entityManager->getRepository(User::class);
                        $user = $userRepository->findOneBy(['email' => $emailUser]);
                        if ($user) {
                            continue;
                        }
                    }
                    if ($roleLabel) {
                        $phone = TrimHelper::safeTrim(isset($item[GridAffectationHeader::USER_PHONE]) ? $item[GridAffectationHeader::USER_PHONE] : null);
                        $fonction = TrimHelper::safeTrim(isset($item[GridAffectationHeader::USER_FONCTION]) ? $item[GridAffectationHeader::USER_FONCTION] : null);
                        $user = $this->userFactory->createInstanceFrom(
                            roleLabel: $roleLabel,
                            firstname: TrimHelper::safeTrim($item[GridAffectationHeader::USER_FIRSTNAME]),
                            lastname: TrimHelper::safeTrim($item[GridAffectationHeader::USER_LASTNAME]),
                            email: $emailUser,
                            phone: $phone ? $phone : null,
                            fonction: $fonction ? $fonction : null,
                        );
                        /** @var ConstraintViolationList $violations */
                        $violations = $this->validator->validate($user);
                        foreach ($violations as $violation) {
                            $errors[] = \sprintf(
                                'line %d : %s Erreur sur le champ %s pour l\'agent %s',
                                $numLine,
                                $violation->getMessage(),
                                $violation->getPropertyPath(),
                                $emailUser
                            );
                        }
                    }
                }
            }
        }

        // check if there are no duplicate email between partners
        $duplicatesMailPartners = $this->checkIfDuplicates($mailPartners);
        if (\count($duplicatesMailPartners) > 0) {
            $errors[] = 'Certains partenaires ont un e-mail en commun '.implode(',', array_keys($duplicatesMailPartners));
        }

        // check if there are no duplicate email between users
        $duplicatesMailUsers = $this->checkIfDuplicates($mailUsers);
        if (\count($duplicatesMailUsers) > 0) {
            $errors[] = 'Certains utilisateurs ont un e-mail en commun '.implode(',', array_keys($duplicatesMailUsers));
        }

        // check if there are no duplicate email between partners and users
        $mails = array_merge($mailPartners, $mailUsers);
        $duplicatesMails = $this->checkIfDuplicates($mails);
        if (\count($duplicatesMails) > 0) {
            $errors[] = 'Certains utilisateurs ont un e-mail en commun avec un partenaire '
                .implode(',', array_keys($duplicatesMails));
        }

        return $errors;
    }

    /**
     * @param array<array<string, mixed>> $data                    Un tableau de données du fichier CSV
     * @param string[]                    $ignoreNotifPartnerTypes
     */
    public function load(
        Territory $territory,
        array $data,
        array $ignoreNotifPartnerTypes,
        ?OutputInterface $output = null,
    ): void {
        $countNewUsers = $countUserMultiTerritory = 0;
        $partner = null;
        $newPartnerNames = [];
        if (null !== $output) {
            $progressBar = new ProgressBar($output, \count($data));
            $progressBar->start();
        }
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        foreach ($data as $item) {
            $canAddUserPartner = false;
            $isNewPartner = false;
            if (\count($item) > 1) {
                $partnerName = TrimHelper::safeTrim($item[GridAffectationHeader::PARTNER_NAME_INSTITUTION]);
                $partnerType = PartnerType::tryFromLabel($item[GridAffectationHeader::PARTNER_TYPE]);

                if (!\in_array($partnerName, $newPartnerNames)) {
                    $partner = $this->partnerManager->findOneBy(['nom' => $partnerName, 'territory' => $territory]);
                    if (null === $partner) {
                        $partner = $this->partnerFactory->createInstanceFrom(
                            territory: $territory,
                            name: $partnerName,
                            email: TrimHelper::safeTrim($item[GridAffectationHeader::PARTNER_EMAIL]),
                            type: $partnerType,
                            insee: $item[GridAffectationHeader::PARTNER_CODE_INSEE]
                        );
                        $isNewPartner = true;
                    }
                    /** @var Partner $partner */

                    /** @var ConstraintViolationList $errors */
                    $errors = $this->validator->validate($partner);
                    if (0 === $errors->count() && $isNewPartner) {
                        $this->partnerManager->save($partner, false);
                        ++$this->metadata['nb_partners'];
                        $newPartnerNames[] = $partnerName;
                    } elseif ($errors->count() > 0) {
                        $this->metadata['errors'][] = \sprintf('%s', (string) $errors);
                        continue;
                    }
                }

                $roleLabel = $this->getRoleLabelFromItem($item);
                $email = TrimHelper::safeTrim($item[GridAffectationHeader::USER_EMAIL]);
                $phone = TrimHelper::safeTrim(isset($item[GridAffectationHeader::USER_PHONE]) ? $item[GridAffectationHeader::USER_PHONE] : null);
                $fonction = TrimHelper::safeTrim(isset($item[GridAffectationHeader::USER_FONCTION]) ? $item[GridAffectationHeader::USER_FONCTION] : null);
                if (!empty($roleLabel) && !empty($email)) {
                    $user = $userRepository->findOneBy(['email' => $email]);
                    if (null === $user) {
                        ++$countNewUsers;
                        $user = $this->userFactory->createInstanceFrom(
                            roleLabel: $roleLabel,
                            firstname: TrimHelper::safeTrim($item[GridAffectationHeader::USER_FIRSTNAME]),
                            lastname: TrimHelper::safeTrim($item[GridAffectationHeader::USER_LASTNAME]),
                            email: $email,
                            isActivateAccountNotificationEnabled: !\in_array($partnerType->name, $ignoreNotifPartnerTypes),
                            phone: $phone,
                            fonction: $fonction
                        );
                        $canAddUserPartner = true;
                    } elseif (!$currentPartner = $user->getPartnerInTerritory($territory)) {
                        ++$countUserMultiTerritory;
                        $canAddUserPartner = true;
                        if ($user->isUsager()) {
                            $user->setRoles([User::ROLES[$roleLabel]]);
                            if (!\in_array($partnerType->name, $ignoreNotifPartnerTypes)) {
                                $this->notificationMailerRegistry->send(
                                    new NotificationMail(
                                        type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_BO,
                                        to: $user->getEmail(),
                                        user: $user,
                                    )
                                );
                            }
                        } elseif (!\in_array($partnerType->name, $ignoreNotifPartnerTypes)) {
                            $this->notificationMailerRegistry->send(
                                new NotificationMail(
                                    type: NotificationMailerType::TYPE_ACCOUNT_NEW_TERRITORY,
                                    to: $user->getEmail(),
                                    user: $user,
                                    territory: $partner->getTerritory(),
                                    params: ['partner_name' => $partner->getNom()]
                                )
                            );
                        }
                    } else {
                        $this->metadata['errors'][] = \sprintf(
                            '%s existe déja sur le territoire %s',
                            $email,
                            $currentPartner->getTerritory()->getName()
                        );
                    }
                    if ($canAddUserPartner) {
                        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
                        $user->addUserPartner($userPartner);

                        /** @var ConstraintViolationList $errors */
                        $errors = $this->validator->validate($user);
                        if (0 === $errors->count()) {
                            $this->userManager->save($user, false);
                            $this->userManager->persist($partner);
                            $this->userManager->persist($userPartner);
                        } else {
                            $this->metadata['errors'][] = \sprintf('line : %s', (string) $errors);
                        }
                    }
                }

                if (0 === $countNewUsers % self::FLUSH_COUNT) {
                    $this->logger->info(\sprintf('in progress - %s users created or updated', $countNewUsers));
                    $this->manager->flush();
                }
            }
            if (null !== $output) {
                $progressBar->advance();
            }
        }

        $this->manager->flush();
        if (null !== $output) {
            $progressBar->finish();
            $progressBar->clear();
        }
        $this->metadata['nb_users_created'] = $countNewUsers;
        $this->metadata['nb_users_multi_territory'] = $countUserMultiTerritory;
    }

    /**
     * @return array{
     *     nb_users_created: int,
     *     nb_users_multi_territory: int,
     *     nb_partners: int,
     *     errors: string[]
     * }
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param string[] $emails Liste des emails à vérifier
     *
     * @return array<string, int> Tableau associatif des emails en doublon
     */
    private function checkIfDuplicates(array $emails): array
    {
        $occurrencesEmails = array_count_values($emails);

        return array_filter($occurrencesEmails, function ($value) {
            return $value > 1;
        });
    }

    /**
     * @param array <string, mixed> $item
     */
    private function getRoleLabelFromItem(array $item): ?string
    {
        $roleLabel = $item[GridAffectationHeader::USER_ROLE];
        if ('Utilisateur' === $roleLabel) {
            $roleLabel = 'Agent';
        } elseif ('Administrateur' === $roleLabel) {
            $roleLabel = 'Admin. partenaire';
        } elseif ('Responsable Territoire' === $roleLabel) {
            $roleLabel = 'Resp. Territoire';
        }
        if (in_array($roleLabel, ['Agent', 'Admin. partenaire', 'Resp. Territoire'])) {
            return $roleLabel;
        }

        return null;
    }
}
