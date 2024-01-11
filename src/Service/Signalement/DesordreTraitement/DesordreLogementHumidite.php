<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreLogementHumidite implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementOuiNon $desordreTraitementOuiNon,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (\array_key_exists($slug.'_details_machine', $payload)) {
            $precisionsDetailMachine = $this->desordreTraitementOuiNon->findDesordresPrecisionsBy(
                $payload,
                $slug.'_details_machine'
            );
            $precisions[] = $precisionsDetailMachine[0];
        }

        if (\array_key_exists($slug.'_details_fuite', $payload)) {
            $precisionsDetailFuite = $this->desordreTraitementOuiNon->findDesordresPrecisionsBy(
                $payload,
                $slug.'_details_fuite'
            );
            $precisions[] = $precisionsDetailFuite[0];
        }

        if (\array_key_exists($slug.'_details_moisissure_apres_nettoyage', $payload)) {
            $precisionsDetailMoisissure = $this->desordreTraitementOuiNon->findDesordresPrecisionsBy(
                $payload,
                $slug.'_details_moisissure_apres_nettoyage'
            );
            $precisions[] = $precisionsDetailMoisissure[0];
        }

        return $precisions;
    }
}
