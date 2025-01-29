<?php

namespace App\Manager;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Exception\User\UserEmailNotFoundException;
use App\Factory\UserFactory;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserManager extends AbstractManager
{
    public const OCCUPANT = 'occupant';
    public const DECLARANT = 'déclarant';

    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private TokenGeneratorInterface $tokenGenerator,
        private ParameterBagInterface $parameterBag,
        protected ManagerRegistry $managerRegistry,
        private SignalementUsagerManager $signalementUsagerManager,
        private UserFactory $userFactory,
        string $entityName = User::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    // TODO : delete with feature_multi_territories deletion
    public function updateUserFromData(User $user, array $data, bool $save = true): User
    {
        $emailUpdated = false;
        if ($user->getEmail() !== $data['email']) {
            $emailUpdated = true;
            $user->setPassword('');
        }
        $user
            ->setNom($data['nom'])
            ->setPrenom($data['prenom'])
            ->setRoles([$data['roles']])
            ->setEmail($data['email'])
            ->setIsMailingActive($data['isMailingActive']);
        if (\array_key_exists('statut', $data)) {
            $user->setStatut($data['statut']);
        }
        if (\array_key_exists('hasPermissionAffectation', $data)) {
            $user->setHasPermissionAffectation($data['hasPermissionAffectation']);
        }

        if ($save) {
            $this->save($user);
        }

        if ($emailUpdated) {
            $this->sendAccountActivationNotification($user);
        }

        return $user;
    }

    public function getUserFrom(Partner $partner, int $userId): ?User
    {
        return $this->getRepository()->findOneBy(['partner' => $partner, 'id' => $userId]);
    }

    public function transferUserToPartner(User $user, Partner $fromPartner, Partner $toPartner): void
    {
        if ($user->hasPartner($toPartner)) {
            return;
        }
        foreach ($user->getUserPartners() as $userPartner) {
            if ($userPartner->getPartner() === $fromPartner) {
                $userPartner->setPartner($toPartner);
                $this->save($userPartner);
                break;
            }
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_TRANSFER,
                to: $user->getEmail(),
                territory: $toPartner->getTerritory(),
                user: $user,
                params: ['partner_name' => $toPartner->getNom()]
            )
        );
    }

    public function resetPassword(User $user, string $password): User
    {
        // if the user is not active yet, he is activating his account, so he just saw the cgu
        $currentCguVersion = $this->parameterBag->get('cgu_current_version');
        if (User::STATUS_ACTIVE !== $user->getStatut()) {
            $user->setCguVersionChecked($currentCguVersion);
        }

        $password = $this->passwordHasherFactory->getPasswordHasher($user)->hash($password);
        $user
            ->setPassword($password)
            ->setToken(null)
            ->setStatut(User::STATUS_ACTIVE)
            ->setTokenExpiredAt(null);

        $this->save($user);
        $password = null;

        return $user;
    }

    public function loadUserToken(string $email, bool $flush = true): User
    {
        /** @var ?User $user */
        $user = $this->findOneBy(['email' => $email]);
        if (null === $user) {
            throw new UserEmailNotFoundException($email);
        }

        return $this->loadUserTokenForUser($user, $flush);
    }

    public function loadUserTokenForUser(User $user, bool $flush = true): User
    {
        $user
            ->setToken($this->tokenGenerator->generateToken())
            ->setTokenExpiredAt(
                (new \DateTimeImmutable())->modify($this->parameterBag->get('token_lifetime'))
            );
        $this->save($user, $flush);

        return $user;
    }

    public function createUsagerFromSignalement(Signalement $signalement, string $type = self::OCCUPANT): ?User
    {
        $mail = (self::OCCUPANT === $type)
            ? $signalement->getMailOccupant()
            : $signalement->getMailDeclarant();

        $prenom = (self::OCCUPANT === $type)
            ? $signalement->getPrenomOccupant()
            : $signalement->getPrenomDeclarant();

        $nom = (self::OCCUPANT === $type)
            ? $signalement->getNomOccupant()
            : $signalement->getNomDeclarant();

        if (null !== $mail) {
            /** @var ?User $user */
            $user = $this->findOneBy(['email' => $mail]);
            if (null === $user) {
                $user = $this->userFactory->createInstanceFrom(
                    roleLabel: User::ROLES['Usager'],
                    firstname: $prenom,
                    lastname: $nom,
                    email: $mail
                );

                $user->setIsMailingActive(true);
                $this->save($user);
            }

            if (self::OCCUPANT === $type) {
                $this->signalementUsagerManager->createOrUpdate($signalement, $user, null);
            } else {
                $this->signalementUsagerManager->createOrUpdate($signalement, null, $user);
            }

            return $user;
        }

        return null;
    }

    public function getOrCreateUserForSignalementAndEmail(Signalement $signalement, ?string $email): ?User
    {
        /** @var User $userOccupant */
        $userOccupant = $this->createUsagerFromSignalement($signalement, self::OCCUPANT);
        /** @var User $userDeclarant */
        $userDeclarant = $this->createUsagerFromSignalement($signalement, self::DECLARANT);
        if ($userOccupant && $email === $userOccupant->getEmail()) {
            return $userOccupant;
        } elseif ($userDeclarant && $email === $userDeclarant->getEmail()) {
            return $userDeclarant;
        }

        return null;
    }

    public function getUserTypeForSignalementAndUser(Signalement $signalement, ?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->getEmail() === $signalement->getMailOccupant()) {
            return self::OCCUPANT;
        } elseif ($user->getEmail() === $signalement->getMailDeclarant()) {
            return self::DECLARANT;
        }

        return null;
    }

    public function getSystemUser(): ?User
    {
        return $this->getRepository()->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
    }

    public function sendAccountActivationNotification(User $user): void
    {
        if (!\in_array('ROLE_USAGER', $user->getRoles())
            && User::STATUS_ARCHIVE !== $user->getStatut()
        ) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_BO,
                    to: $user->getEmail(),
                    user: $user,
                )
            );
        }
    }
}
