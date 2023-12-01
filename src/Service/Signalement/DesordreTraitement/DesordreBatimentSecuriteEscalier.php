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

        if (isset($payload['desordres_batiment_securite_escalier_details_utilisable'])
            && 'oui' === $payload['desordres_batiment_securite_escalier_details_utilisable']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_securite_escalier_details_utilisable']
            );
            $precisions->add($precision);
        }
        if (isset($payload['desordres_batiment_securite_escalier_details_dangereux'])
            && 'oui' === $payload['desordres_batiment_securite_escalier_details_dangereux']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_securite_escalier_details_dangereux']
            );
            $precisions->add($precision);
        }

        return $precisions;
    }
}
