<?php

namespace App\EventListener;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Intervention::class)]
class InterventionEditedListener
{
    public function postUpdate(Intervention $intervention, PostUpdateEventArgs $event): void
    {
        $changeSet = $event->getObjectManager()
            ->getUnitOfWork()
            ->getEntityChangeSet($intervention);

        if ($this->supports($intervention) && (isset($changeSet['details']) || isset($changeSet['concludeProcedure']))) {
            $intervention->setConclusionVisiteEditedAt(new \DateTimeImmutable());

            $fields = ['details', 'concludeProcedure'];
            $changes = [];

            foreach ($fields as $field) {
                if (!isset($changeSet[$field])) {
                    continue;
                }
                $before = $changeSet[$field][0] ?? null;
                $after = $changeSet[$field][1] ?? null;

                if ('concludeProcedure' === $field) {
                    $before = is_array($before)
                        ? array_map(fn (string $procedure) => ProcedureType::tryFrom($procedure)->label(), $before)
                        : [];
                    $after = is_array($after)
                        ? array_map(fn (string $procedure) => ProcedureType::tryFrom($procedure)->label(), $after)
                        : [];
                }

                if (!empty($before) || !empty($after)) {
                    $changes[$field] = [
                        'old' => is_array($before) ? implode(', ', $before) : $before,
                        'new' => is_array($after) ? implode(', ', $after) : $after,
                    ];
                }
            }

            $intervention->setChangesForMail($changes);
        }
    }

    public function supports(Intervention $intervention): bool
    {
        return Intervention::STATUS_DONE === $intervention->getStatus() && InterventionType::VISITE === $intervention->getType();
    }
}
