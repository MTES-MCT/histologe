<?php

namespace App\EventListener;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private const CHECK_2FA_PATH = '/2fa_check';

    public function __construct(
        private EntityManagerInterface $em,
        private HistoryEntryManager $historyEntryManager,
        private LoggerInterface $logger
    ) {
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $user->setLastLoginAt(new DateTimeImmutable());

        try {
            if (self::CHECK_2FA_PATH !== $event->getRequest()->getPathInfo()) {
                /*$this->historyEntryManager->create(
                    historyEntryEvent: HistoryEntryEvent::LOGIN,
                    entityId: $user->getId(),
                    entityName: User::class,
                    user: $user
                );*/
                $historyEntry = new HistoryEntry();
                $historyEntry->setEvent(HistoryEntryEvent::LOGIN);
                $historyEntry->setEntityId($user->getId());
                $historyEntry->setEntityName(User::class);
                $historyEntry->setUser($user);
                $this->em->persist($historyEntry);
            }

            $event->getRequest()->getSession()->set('_security.territory', $user->getTerritory());
            // Persist the data to database.
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Exception $e) {
            $msg = 'Failed to create login history entry ('.$e->getMessage().') on user :"'.$user->getId();
            $this->logger->error($msg);
            throw new \Exception($msg);
        }
    }
}
