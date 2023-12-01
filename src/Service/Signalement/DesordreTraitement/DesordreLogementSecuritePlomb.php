<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreLogementSecuritePlomb implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        if (isset($payload['desordres_logement_securite_plomb_details_diagnostique'])) {
            if ('oui' === $payload['desordres_logement_securite_plomb_details_diagnostique']) {
                $precisions = $this->desordreTraitementPieces->process(
                    $payload,
                    'desordres_logement_securite_plomb_pieces',
                    '_diagnostique_oui'
                );
            } else {
                $precisions = $this->desordreTraitementPieces->process(
                    $payload,
                    'desordres_logement_securite_plomb_pieces',
                    '_diagnostique_non'
                );
            }
        }

        return $precisions;
    }
}
