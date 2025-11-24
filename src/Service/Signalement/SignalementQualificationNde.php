<?php

namespace App\Service\Signalement;

use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Repository\CriticiteRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\SignalementQualificationRepository;

class SignalementQualificationNde
{
    public function __construct(
        private readonly SignalementQualificationRepository $signalementQualificationRepository,
        private readonly CriticiteRepository $criticiteRepository,
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    /**
     * @return array{0: ?SignalementQualification, 1: ?array<mixed>}
     */
    public function getSignalementQualificationNdeAndCriticites(Signalement $signalement): array
    {
        $signalementQualificationNDE = $this->signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE
        ]);

        if (!$signalement->isV2()) {
            $signalementQualificationNDECriticites = $signalementQualificationNDE
                ? $this->criticiteRepository->findBy(['id' => $signalementQualificationNDE->getCriticites()])
                : null;
        } else {
            $signalementQualificationNDECriticites = $signalementQualificationNDE
                ? $this->desordrePrecisionRepository->findBy(
                    ['id' => $signalementQualificationNDE->getDesordrePrecisionIds()]
                )
                : null;
        }

        return [$signalementQualificationNDE, $signalementQualificationNDECriticites];
    }

}
