<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreBatimentIsolationInfiltrationEau implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        if ('maison' === $payload['type_logement_nature']
            || (\array_key_exists('type_logement_sous_sol_sans_fenetre', $payload) && 'oui' === $payload['type_logement_sous_sol_sans_fenetre'])
            || (\array_key_exists('type_logement_rdc', $payload) && 'oui' === $payload['type_logement_rdc'])) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_infiltration_eau_au_sol_oui']
            );
        } else {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_infiltration_eau_au_sol_non']
            );
        }
        $precisions->add($precision);

        return $precisions;
    }
}
