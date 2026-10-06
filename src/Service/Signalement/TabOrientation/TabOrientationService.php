<?php

namespace App\Service\Signalement\TabOrientation;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Repository\InterventionRepository;

class TabOrientationService
{
    public function __construct(private readonly InterventionRepository $interventionRepository)
    {
    }

    /**
     * @return array<TabOrientationItem>
     */
    public function getTabOrientationItems(Signalement $signalement): array
    {
        $items = [];
        $items[] = new TabOrientationItem(signalement: $signalement, type: TabOrientationItem::TYPE_PRE_EVALUATION);
        $items[] = new TabOrientationItem(signalement: $signalement, type: TabOrientationItem::TYPE_PROCEDURE_A_ENGAGER);
        if (SignalementStatus::CLOSED === $signalement->getStatut() && $signalement->getClosedAt()) {
            $items[] = new TabOrientationItem(signalement: $signalement, type: TabOrientationItem::TYPE_PROCEDURE_RETENUE);
        }
        foreach ($this->interventionRepository->getVisitesDoneForSignalement($signalement) as $visite) {
            $items[] = new TabOrientationItem(signalement: $signalement, type: TabOrientationItem::TYPE_CONCLUSION_DE_VISITE, intervention: $visite);
        }

        // tri des éléments par date décroissante
        usort($items, static fn (TabOrientationItem $a, TabOrientationItem $b): int => $b->getDate() <=> $a->getDate());

        return $items;
    }
}
