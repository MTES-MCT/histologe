<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntityHistoryCollectionInterface;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Manager\HistoryEntryManager;
use App\Service\History\EntityComparator;
use App\Service\History\HistoryEntryBuffer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsDoctrineListener(event: Events::onFlush)]
class EntityHistoryListener
{
    private UnitOfWork $uow;

    public const array FIELDS_TO_IGNORE = [
        'lastLoginAt',
        'updatedAt',
        'lastSuiviAt',
        'lastSuiviBy',
        'lastSuiviIsPublic',
    ];

    public function __construct(
        private readonly HistoryEntryManager $historyEntryManager,
        private readonly EntityComparator $entityComparator,
        private readonly LoggerInterface $logger,
        private readonly HistoryEntryBuffer $historyEntryBuffer,
        #[Autowire(env: 'HISTORY_TRACKING_ENABLE')]
        private readonly string $historyTrackingEnable,
    ) {
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->historyTrackingEnable) {
            return;
        }
        $this->uow = $eventArgs->getObjectManager()->getUnitOfWork();
        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            try {
                $this->processEntity($entity, HistoryEntryEvent::CREATE);
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            try {
                $this->processEntity($entity, HistoryEntryEvent::UPDATE);
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            try {
                $this->processEntity($entity, HistoryEntryEvent::DELETE);
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        foreach ($this->uow->getScheduledCollectionUpdates() as $col) {
            try {
                if ($col instanceof PersistentCollection) {
                    $this->processCollection($col, HistoryEntryEvent::UPDATE);
                }
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function processEntity(object $entity, HistoryEntryEvent $event): void
    {
        if (!$entity instanceof EntityHistoryInterface || !in_array($event, $entity->getHistoryRegisteredEvent())) {
            return;
        }

        $changes = [];
        if (HistoryEntryEvent::UPDATE === $event) {
            foreach ($this->uow->getEntityChangeSet($entity) as $field => $changed) {
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

    public function processCollection(PersistentCollection $collection, HistoryEntryEvent $event): void
    {
        $changes = [];
        $ownerEntity = $collection->getOwner();
        $fieldName = $collection->getMapping()['fieldName'];
        if ($ownerEntity instanceof EntityHistoryCollectionInterface && in_array($fieldName, $ownerEntity->getManyToManyFieldsToTrack())) {
            foreach ($collection->getInsertDiff() as $insertItem) {
                /* @var EntityHistoryInterface $insertItem */
                $changes[$fieldName]['new'][] = $insertItem->getId();
            }

            foreach ($collection->getDeleteDiff() as $deleteItem) {
                /* @var EntityHistoryInterface $deleteItem */
                $changes[$fieldName]['old'][] = $deleteItem->getId();
            }
            $this->saveEntityHistory($event, $ownerEntity, $changes); // @phpstan-ignore-line
        }
    }

    public function saveEntityHistory(
        HistoryEntryEvent $event,
        EntityHistoryInterface $entity,
        array $changes,
    ): void {
        try {
            $id = $entity->getId() ?? Uuid::v4();
            $historyKey = $entity::class.'_'.$id.'_'.$event->value;
            if (HistoryEntryEvent::CREATE !== $event && $this->historyEntryBuffer->exist($historyKey)) {
                $this->historyEntryBuffer->update($historyKey, $changes);
            } else {
                $historyEntry = $this->historyEntryManager->create(
                    historyEntryEvent: $event,
                    entityHistory: $entity,
                    changes: $changes,
                );
                $this->historyEntryBuffer->add($historyKey, $historyEntry);
            }
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
