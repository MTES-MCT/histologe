<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Manager\HistoryEntryManager;
use App\Service\History\EntityComparator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
readonly class EntityHistoryListener
{
    public const array FIELDS_TO_IGNORE = [
        'password',
        'token',
        'idossToken',
        'esaboraToken',
    ];

    public function __construct(
        private HistoryEntryManager $historyEntryManager,
        private EntityManagerInterface $entityManager,
        private EntityComparator $entityComparator,
    ) {
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     */
    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $this->processEntity($eventArgs, HistoryEntryEvent::CREATE);
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     */
    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $this->processEntity($eventArgs, HistoryEntryEvent::UPDATE);
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     */
    public function preRemove(LifecycleEventArgs $eventArgs): void
    {
        $this->processEntity($eventArgs, HistoryEntryEvent::DELETE);
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     */
    protected function processEntity(LifecycleEventArgs $eventArgs, HistoryEntryEvent $event): void
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof EntityHistoryInterface || !in_array($event, $entity->getHistoryRegisteredEvent())) {
            return;
        }

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $changes = [];
        if (HistoryEntryEvent::UPDATE === $event) {
            foreach ($unitOfWork->getEntityChangeSet($entity) as $fields => $changed) {
                $originalValue = $changed[0] ?? null;
                $newValue = $changed[1] ?? null;

                $originalValue = $this->entityComparator->processValue($originalValue);
                $newValue = $this->entityComparator->processValue($newValue);

                $fieldChanges = $this->entityComparator->compareValues($originalValue, $newValue, $fields);
                if (!empty($fieldChanges)) {
                    $changes[$fields] = $fieldChanges;
                }
            }
        }

        if (HistoryEntryEvent::UPDATE === $event && empty($changes)) {
            return;
        }

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: $event,
            entityHistory: $entity,
            flush: false
        );

        [$changesFromDelete, $source] = $this->historyEntryManager->getChangesAndSource($event, $entity);
        $historyEntry
            ->setChanges(HistoryEntryEvent::DELETE === $event ? $changesFromDelete : $changes)
            ->setSource($source);

        $this->historyEntryManager->save($historyEntry);
    }
}
