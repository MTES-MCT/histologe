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
    public const DECLARANT = 'declarant';

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

    public function updateUserFromData(User $user, array $data): User
    {
        $user
            ->setNom($data['nom'])
            ->setPrenom($data['prenom'])
            ->setRoles([$data['roles']])
            ->setEmail($data['email'])
            ->setIsMailingActive($data['isMailingActive']);

        $this->save($user);
        // TODO envoi du mail ?
        return $user;
    }

    public function createUserFromData(Partner $partner, array $data): User
    {
        $user = $this->userFactory->createInstanceFromArray($partner, $data);

        $this->save($user);
        // TODO envoi du mail ?
        return $user;
    }

    public function getUserFrom(Partner $partner, int $userId): ?User
    {
        return $this->getRepository()->findOneBy(['partner' => $partner, 'id' => $userId]);
    }

    public function transferUserToPartner(User $user, Partner $partner): void
    {
        $user->setPartner($partner);
        $this->save($user);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_TRANSFER,
                to: $user->getEmail(),
                territory: $user->getTerritory(),
                user: $user
            )
        );
    }

    public function resetPassword(User $user, string $password): User
    {
        $password = $this->passwordHasherFactory->getPasswordHasher($user)->hash($password);
        $user
            ->setPassword($password)
            ->setToken(null)
            ->setStatut(User::STATUS_ACTIVE)
            ->setTokenExpiredAt(null);

        $this->save($user);

        return $user;
    }

    public function loadUserToken(string $email): User
    {
        /** @var User $user */
        $user = $this->findOneBy(['email' => $email]);
        if (null === $user) {
            throw new UserEmailNotFoundException($email);
        }
        $user
            ->setToken($this->tokenGenerator->generateToken())
            ->setTokenExpiredAt(
                (new \DateTimeImmutable())->modify($this->parameterBag->get('token_lifetime'))
            );

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
            /** @var User $user */
            $user = $this->findOneBy(['email' => $mail]);
            if (null === $user) {
                $user = $this->userFactory->createInstanceFrom(
                    roleLabel: User::ROLES['Usager'],
                    territory: null,
                    partner: null,
                    firstname: $prenom,
                    lastname: $nom,
                    email: $mail
                );

                $user->setIsMailingActive(true);
                $user->setStatut(User::STATUS_ACTIVE);
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
}
