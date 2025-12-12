<?php

namespace App\Factory;

use App\Entity\Affectation;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Security\User\SignalementBailleur;
use App\Security\User\SignalementUser;
use Symfony\Bundle\SecurityBundle\Security;

readonly class HistoryEntryFactory
{
    public const string ENTITY_PROXY_PREFIX = 'Proxies\\__CG__\\';

    public function __construct(private Security $security)
    {
    }

    public function createInstanceFrom(
        HistoryEntryEvent $historyEntryEvent,
        EntityHistoryInterface $entityHistory,
    ): HistoryEntry {
        /** @var User|SignalementBailleur $user */
        $user = $this->security->getUser();
        if ($user instanceof SignalementUser) {
            $user = $user->getUser();
        } elseif ($user instanceof SignalementBailleur) {
            $user = null;
        }
        $historyEntry = (new HistoryEntry())
            ->setEvent($historyEntryEvent)
            ->setEntity($entityHistory)
            ->setEntityId($entityHistory->getId())
            ->setEntityName(str_replace(self::ENTITY_PROXY_PREFIX, '', $entityHistory::class))
            ->setUser($user);

        if ($entityHistory instanceof Affectation || $entityHistory instanceof UserSignalementSubscription) {
            $historyEntry->setSignalement($entityHistory->getSignalement());
        }
        if ($entityHistory instanceof Affectation && HistoryEntryEvent::CREATE === $historyEntryEvent) {
            $historyEntry->setUser($entityHistory->getAffectedBy());
        }

        return $historyEntry;
    }
}
