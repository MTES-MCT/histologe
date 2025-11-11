<?php

namespace App\Manager;

use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
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
        if (UserStatus::ACTIVE !== $user->getStatut()) {
            $user->setCguVersionChecked($currentCguVersion);
        }

        $password = $this->passwordHasherFactory->getPasswordHasher($user)->hash($password);
        $user
            ->setPassword($password)
            ->setToken(null)
            ->setStatut(UserStatus::ACTIVE)
            ->setTokenExpiredAt(null);

        $this->save($user);
        $password = null;

        return $user;
    }

    public static function getComplexRandomPassword(): string
    {
        $keyspace = '23456789abcdefghjkmnopqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ!:()';
        $password = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < 15; ++$i) {
            $password .= $keyspace[random_int(0, $max)];
        }

        return $password;
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

    public function createUsagersFromSignalement(Signalement $signalement): void
    {
        $this->createUsagerFromSignalement($signalement);
        $this->createUsagerFromSignalement($signalement, self::DECLARANT);
    }

    public function createUsagerFromSignalement(Signalement $signalement, string $type = self::OCCUPANT): ?User
    {
        $user = null;
        $mail = (self::OCCUPANT === $type)
            ? $signalement->getMailOccupant()
            : $signalement->getMailDeclarant();

        $prenom = (self::OCCUPANT === $type)
            ? $signalement->getPrenomOccupant()
            : $signalement->getPrenomDeclarant();

        $nom = (self::OCCUPANT === $type)
            ? $signalement->getNomOccupant()
            : $signalement->getNomDeclarant();

        if (!$mail && self::OCCUPANT === $type) {
            $mail = 'sl__'.uniqid().'@signal-logement.fr';
        }
        if ($mail) {
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
        }

        if (self::OCCUPANT === $type) {
            $this->signalementUsagerManager->createOrUpdate($signalement, $user, null);
        } else {
            $this->signalementUsagerManager->createOrUpdate($signalement, null, $user);
        }

        return $user;
    }

    public function getSystemUser(): ?User
    {
        return $this->getRepository()->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
    }

    public function sendAccountActivationNotification(User $user): void
    {
        if (!\in_array('ROLE_USAGER', $user->getRoles())
            && UserStatus::ARCHIVE !== $user->getStatut()
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
