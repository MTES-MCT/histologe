<?php

namespace App\EventListener;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private const CHECK_2FA_PATH = '/2fa_check';

    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private HistoryEntryManager $historyEntryManager
    ) {
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $user->setLastLoginAt(new DateTimeImmutable());

        if (self::CHECK_2FA_PATH !== $event->getRequest()->getPathInfo()) {
            $this->historyEntryManager->create(
                historyEntryEvent: HistoryEntryEvent::LOGIN,
                entityId: $user->getId(),
                entityName: User::class,
                user: $user
            );
        }

        $this->requestStack->getSession()->set('_security.territory', $user->getTerritory());
        // Persist the data to database.
        $this->em->persist($user);
        $this->em->flush();
    }
}
