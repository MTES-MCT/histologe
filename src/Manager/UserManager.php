<?php

namespace App\Manager;

use App\Entity\Partner;
use App\Entity\User;
use App\Service\NotificationService;
use App\Service\Token\TokenGeneratorInterface;
use App\Exception\User\UserEmailNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserManager extends AbstractManager
{
    public function __construct(
        private LoginLinkHandlerInterface $loginLinkHandler,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private TokenGeneratorInterface $tokenGenerator,
        private ParameterBagInterface $parameterBag,
        protected ManagerRegistry $managerRegistry,
        string $entityName = User::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function updateUserFromData(User $user, array $data): User
    {
        $user
            ->setNom($data['nom'])
            ->setPrenom($data['prenom'])
            ->setRoles([$data['roles']])
            ->setEmail($data['email'])
            ->setIsGenerique($data['isGenerique'])
            ->setIsMailingActive($data['isMailingActive']);

        return $user;
    }

    public function getUserFrom(Partner $partner, int $userId): ?User
    {
        return $this->getRepository()->findOneBy(['partner' => $partner, 'id' => $userId]);
    }

    public function switchUserToPartner(User $user, Partner $partner): void
    {
        $user->setPartner($partner);
        $this->save($user);

        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
        $loginLink = $loginLinkDetails->getUrl();

        $link = User::STATUS_ACTIVE === $user->getStatut() ?
            $this->urlGenerator->generate('back_index') :
            $loginLink;

        $this->notificationService->send(
            NotificationService::TYPE_ACCOUNT_SWITCH,
            $user->getEmail(), [
            'btntext' => User::STATUS_ACTIVE === $user->getStatut() ? 'Accéder à mon compte' : 'Activer mon compte',
            'link' => $link,
            'user_status' => $user->getStatut(),
            'partner_name' => $partner->getNom(),
        ],
            $user->getTerritory()
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
}
