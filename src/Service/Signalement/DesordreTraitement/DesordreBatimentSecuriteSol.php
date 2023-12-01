<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreBatimentSecuriteSol implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        if (isset($payload['desordres_batiment_securite_sol_details_plancher_abime'])
            && 'oui' === $payload['desordres_batiment_securite_sol_details_plancher_abime']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_securite_sol_details_plancher_abime']
            );
            $precisions->add($precision);
        }
        if (isset($payload['desordres_batiment_securite_sol_details_plancher_effondre'])
            && 'oui' === $payload['desordres_batiment_securite_sol_details_plancher_effondre']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_securite_sol_details_plancher_effondre']
            );
            $precisions->add($precision);
        }

        return $precisions;
    }
}
