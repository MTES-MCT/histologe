<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreBatimentSecuriteEscalier implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        $slugUtilisable = 'desordres_batiment_securite_escalier_details_utilisable';
        if (isset($payload[$slugUtilisable])
            && 'oui' === $payload[$slugUtilisable]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugUtilisable]
            );
            $precisions->add($precision);
        }
        $slugDangereux = 'desordres_batiment_securite_escalier_details_dangereux';
        if (isset($payload[$slugDangereux])
            && 'oui' === $payload[$slugDangereux]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugDangereux]
            );
            $precisions->add($precision);
        }

        return $precisions;
    }
}
