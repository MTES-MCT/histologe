<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntityHistoryCollectionInterface;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Manager\HistoryEntryManager;
use App\Service\History\EntityComparator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
readonly class EntityHistoryListener
{
    public const array FIELDS_TO_IGNORE = [
        'lastLoginAt',
        'updatedAt',
        'lastSuiviAt',
        'lastSuiviBy',
        'lastSuiviIsPublic',
    ];

    public function __construct(
        private HistoryEntryManager $historyEntryManager,
        private EntityManagerInterface $entityManager,
        private EntityComparator $entityComparator,
        private LoggerInterface $logger,
        #[Autowire(env: 'HISTORY_TRACKING_ENABLE')]
        private string $historyTrackingEnable,
    ) {
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        try {
            $this->processEntity($eventArgs, HistoryEntryEvent::CREATE);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        try {
            $this->processEntity($eventArgs, HistoryEntryEvent::UPDATE);
            $this->processCollection(HistoryEntryEvent::UPDATE);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function preRemove(LifecycleEventArgs $eventArgs): void
    {
        try {
            $this->processEntity($eventArgs, HistoryEntryEvent::DELETE);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function processEntity(LifecycleEventArgs $eventArgs, HistoryEntryEvent $event): void
    {
        $entity = $eventArgs->getObject();
        if (!$this->historyTrackingEnable
            || !$entity instanceof EntityHistoryInterface
            || !in_array($event, $entity->getHistoryRegisteredEvent())) {
            return;
        }

        $changes = [];
        if (HistoryEntryEvent::UPDATE === $event) {
            $unitOfWork = $this->entityManager->getUnitOfWork();
            foreach ($unitOfWork->getEntityChangeSet($entity) as $field => $changed) {
                if (in_array($field, self::FIELDS_TO_IGNORE)) {
                    continue;
                }
                $originalValue = $changed[0] ?? null;
                $newValue = $changed[1] ?? null;

                if (is_array($originalValue) && is_array($newValue)) {
                    $originalValue = $this->sortKey($originalValue);
                    $newValue = $this->sortKey($newValue);
                }

                $originalValue = $this->entityComparator->processValue($originalValue);
                $newValue = $this->entityComparator->processValue($newValue);



                $fieldChanges = $this->entityComparator->compareValues($originalValue, $newValue, $field);
                if (!empty($fieldChanges)) {
                    $changes[$field] = $fieldChanges;
                }
            }
        }
        if (HistoryEntryEvent::DELETE === $event) {
            $changes = $this->entityComparator->getEntityPropertiesAndValueNormalized($entity);
        }

        if (in_array($event, [HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE]) && empty($changes)) {
            return;
        }

        $this->saveEntityHistory($event, $entity, $changes);
    }

    public function processCollection(HistoryEntryEvent $event): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $changes = [];
        foreach ($unitOfWork->getScheduledCollectionUpdates() as $collection) {
            $ownerEntity = $collection->getOwner();
            $fieldName = $collection->getMapping()['fieldName'];
            if ($ownerEntity instanceof EntityHistoryCollectionInterface
                && in_array($fieldName, $ownerEntity->getManyToManyFieldsToTrack())
            ) {
                foreach ($collection->getInsertDiff() as $insertItem) {
                    /* @var EntityHistoryInterface $insertItem */
                    $changes[$fieldName]['new'][] = $insertItem->getId();
                }

                foreach ($collection->getDeleteDiff() as $deleteItem) {
                    /* @var EntityHistoryInterface $deleteItem */
                    $changes[$fieldName]['old'][] = $deleteItem->getId();
                }
                $this->saveEntityHistory($event, $ownerEntity, $changes);
            }
        }
    }

    public function saveEntityHistory(
        HistoryEntryEvent $event,
        EntityHistoryInterface $entity,
        array $changes,
    ): void {
        try {
            $this->historyEntryManager->create(
                historyEntryEvent: $event,
                entityHistory: $entity,
                changes: $changes
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function sortKey(array $data): array
    {
        ksort($data);

        return $data;
    }
}
