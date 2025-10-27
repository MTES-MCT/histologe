<?php

namespace App\EventListener;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Service\HtmlCleaner;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Intervention::class)]
class InterventionEditedListener
{
    public function preUpdate(Intervention $intervention, PreUpdateEventArgs $event): void
    {
        if (!$this->supports($intervention)) {
            return;
        }

        $changes = [];
        if ($event->hasChangedField('details')) {
            if (!empty($event->getOldValue('details'))) {
                $before = preg_replace(
                    '/\s+/',
                    ' ',
                    HtmlCleaner::clean($event->getOldValue('details'))
                );
                $after = preg_replace(
                    '/\s+/',
                    ' ',
                    HtmlCleaner::clean($event->getNewValue('details'))
                );

                if ($before !== $after && !empty($after)) {
                    $changes['details'] = [
                        'old' => $before,
                        'new' => $after,
                    ];
                }
            }
        }

        if ($event->hasChangedField('concludeProcedure')) {
            $before = $event->getOldValue('concludeProcedure') ?? [];
            $after = $event->getNewValue('concludeProcedure') ?? [];
            if (!empty($before)) {
                $before = is_array($before)
                    ? array_map(fn (string $procedure) => ProcedureType::tryFrom($procedure)->label(), $before)
                    : [];
                $after = is_array($after)
                    ? array_map(fn (string $procedure) => ProcedureType::tryFrom($procedure)->label(), $after)
                    : [];

                if ($before !== $after && !empty($after)) {
                    $changes['concludeProcedure'] = [
                        'old' => implode(', ', $before),
                        'new' => implode(', ', $after),
                    ];
                }
            }
        }

        if (!empty($changes)) {
            $intervention->setChangesForMail($changes);
            $intervention->setConclusionVisiteEditedAt(new \DateTimeImmutable());
        }
    }

    public function supports(Intervention $intervention): bool
    {
        return Intervention::STATUS_DONE === $intervention->getStatus()
            && InterventionType::VISITE === $intervention->getType();
    }
}
