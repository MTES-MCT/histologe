<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreLogementAerationVentilationDefectueuse implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        if (isset($payload['desordres_logement_aeration_ventilation_defectueuse_details_nettoyage'])) {
            if ('oui' === $payload['desordres_logement_aeration_ventilation_defectueuse_details_nettoyage']) {
                $precisions = $this->desordreTraitementPieces->process(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details_pieces',
                    '_nettoyage_oui'
                );
            } else {
                $precisions = $this->desordreTraitementPieces->process(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details_pieces',
                    '_nettoyage_non'
                );
            }
        }

        return $precisions;
    }
}
