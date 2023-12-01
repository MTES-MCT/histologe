<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreLogementHumidite implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementOuiNon $desordreTraitementOuiNon,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        if (isset($payload[$slug.'_details_machine'])) {
            $precisionsDetailMachine = $this->desordreTraitementOuiNon->process(
                $payload,
                $slug.'_details_machine'
            );
            $precisions->add($precisionsDetailMachine->first());
        }

        if (isset($payload[$slug.'_details_fuite'])) {
            $precisionsDetailFuite = $this->desordreTraitementOuiNon->process(
                $payload,
                $slug.'_details_fuite'
            );
            $precisions->add($precisionsDetailFuite->first());
        }

        if (isset($payload[$slug.'_details_moisissure_apres_nettoyage'])) {
            $precisionsDetailMoisissure = $this->desordreTraitementOuiNon->process(
                $payload,
                $slug.'_details_moisissure_apres_nettoyage'
            );
            $precisions->add($precisionsDetailMoisissure->first());
        }
        // TODO : voir avec Mathilde : Dans cette partie on est obligés de choisir pièce à vire, cuisine, salle de bain, on ne peut pas avoir une sorte de "tout le logement"

        return $precisions;
    }
}
