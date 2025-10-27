<?php

namespace App\EventListener;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Service\HtmlCleaner;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use function Symfony\Component\String\u;

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
                $before = self::normalizeText($event->getOldValue('details'));
                $after = self::normalizeText($event->getNewValue('details'));

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
                sort($before);
                sort($after);
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

    private function normalizeText(?string $text): string
    {
        if (null === $text) {
            return '';
        }

        return u(HtmlCleaner::clean($text))
            ->replaceMatches('/\R/u', "\n")          // normalise tous les retours à la ligne
            ->replaceMatches('/\x{00A0}/u', ' ')     // remplace les espaces insécables
            ->replaceMatches('/[ \t\n]+/u', ' ')     // réduit tous les espaces multiples
            ->normalize()                            // normalise Unicode (accents, etc.)
            ->trim()
            ->toString();
    }
}
