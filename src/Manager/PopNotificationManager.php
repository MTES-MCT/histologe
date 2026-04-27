<?php

namespace App\Manager;

use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\PopNotification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class PopNotificationManager extends Manager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        protected ManagerRegistry $managerRegistry,
        string $entityName = PopNotification::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdatePopNotification(
        User $user,
        string $type,
        Partner $partner,
    ): ?PopNotification {
        if (UserStatus::ACTIVE !== $user->getStatut()) {
            return null;
        }

        /** @var PopNotification|null $popNotification */
        $popNotification = $user->getPopNotifications()->first() ?: null;

        if (!$popNotification) {
            $popNotification = new PopNotification();
            $user->addPopNotification($popNotification);
            $this->entityManager->persist($popNotification);
            $popNotification->setUser($user);
        }
        switch ($type) {
            case 'addPartner':
                $this->managePartners($popNotification, $partner, 'add');
                break;
            case 'removePartner':
                $this->managePartners($popNotification, $partner, 'remove');
                break;
        }
        if (empty($popNotification->getParams()['addedPartners']) && empty($popNotification->getParams()['removedPartners'])) {
            $this->entityManager->remove($popNotification);

            return null;
        }

        return $popNotification;
    }

    private function managePartners(PopNotification $popNotification, Partner $partner, string $type): void
    {
        $keyToAdd = 'addedPartners';
        $keyToRemove = 'removedPartners';
        if ('remove' === $type) {
            $keyToAdd = 'removedPartners';
            $keyToRemove = 'addedPartners';
        }
        $list = isset($popNotification->getParams()[$keyToRemove]) ? $popNotification->getParams()[$keyToRemove] : [];
        if (!empty($list) && in_array($partner->getId(), $list)) {
            unset($list[array_search($partner->getId(), $list)]);
            $params = array_merge($popNotification->getParams(), [$keyToRemove => $list]);
            $popNotification->setParams($params);

            return;
        }
        $list = isset($popNotification->getParams()[$keyToAdd]) ? $popNotification->getParams()[$keyToAdd] : [];
        $list[] = $partner->getId();
        $params = array_merge($popNotification->getParams(), [$keyToAdd => $list]);
        $popNotification->setParams($params);
    }
}
