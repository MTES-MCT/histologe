<?php

namespace App\Specification\Signalement;

use App\Entity\Affectation;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Repository\SuiviRepository;
use Doctrine\Common\Collections\Collection;

class FirstAffectationAcceptedSpecification
{
    public function __construct(private SuiviRepository $suiviRepository)
    {
    }

    public function isSatisfiedBy(Signalement $signalement, Affectation $affectation): bool
    {
        $suiviAffectationAccepted = $this->suiviRepository->findSuiviByDescription(
            $signalement,
            '<p>Suite à votre signalement, le ou les partenaires compétents'
        );
        $affectationAccepted = $signalement->getAffectations()->filter(function (Affectation $affectation) {
            return Affectation::STATUS_ACCEPTED === $affectation->getStatut();
        });

        $interventions = $signalement
            ->getInterventions()
            ->filter(function (Intervention $intervention) {
                return Intervention::STATUS_PLANNED === $intervention->getStatus()
                    || Intervention::STATUS_DONE === $intervention->getStatus();
            });

        return $this->canWriteSuiviMessage(
            $signalement,
            $affectation,
            $affectationAccepted,
            $interventions,
            $suiviAffectationAccepted
        );
    }

    private function canWriteSuiviMessage(
        Signalement $signalement,
        Affectation $affectation,
        Collection $affectationAccepted,
        Collection $interventions,
        array $suiviAffectationAccepted
    ): bool {
        return !$signalement->getIsImported()
        && 1 === $affectationAccepted->count()
        && Affectation::STATUS_ACCEPTED === $affectation->getStatut()
        && empty($suiviAffectationAccepted)
        && $interventions->isEmpty();
    }
}
