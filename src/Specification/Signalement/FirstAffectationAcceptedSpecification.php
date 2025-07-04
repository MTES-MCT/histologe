<?php

namespace App\Specification\Signalement;

use App\Entity\Affectation;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SuiviRepository;
use Doctrine\Common\Collections\Collection;

readonly class FirstAffectationAcceptedSpecification
{
    public function __construct(private SuiviRepository $suiviRepository)
    {
    }

    public function isSatisfiedBy(Affectation $affectation): bool
    {
        $signalement = $affectation->getSignalement();
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

    /**
     * @param Collection<int, Affectation>  $affectationAccepted,
     * @param Collection<int, Intervention> $interventions,
     * @param array<Suivi>                  $suiviAffectationAccepted,
     */
    private function canWriteSuiviMessage(
        Signalement $signalement,
        Affectation $affectation,
        Collection $affectationAccepted,
        Collection $interventions,
        array $suiviAffectationAccepted,
    ): bool {
        return !$signalement->getIsImported()
        && 1 === $affectationAccepted->count()
        && Affectation::STATUS_ACCEPTED === $affectation->getStatut()
        && SignalementStatus::ACTIVE === $signalement->getStatut()
        && empty($suiviAffectationAccepted)
        && $interventions->isEmpty();
    }
}
