<?php

namespace App\Manager;

use App\Entity\Partner;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class UserManager extends AbstractManager
{
    public function __construct(
        private LoginLinkHandlerInterface $loginLinkHandler,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
        ManagerRegistry $managerRegistry,
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
}
