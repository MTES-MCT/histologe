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

        $slugAbime = 'desordres_batiment_securite_sol_details_plancher_abime';
        if (isset($payload[$slugAbime])
            && 'oui' === $payload[$slugAbime]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugAbime]
            );
            $precisions->add($precision);
        }
        $slugEffondre = 'desordres_batiment_securite_sol_details_plancher_effondre';
        if (isset($payload[$slugEffondre])
            && 'oui' === $payload[$slugEffondre]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugEffondre]
            );
            $precisions->add($precision);
        }

        return $precisions;
    }
}
