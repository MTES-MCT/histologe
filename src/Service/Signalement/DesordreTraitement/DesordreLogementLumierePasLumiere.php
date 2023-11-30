<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: 'desordres_logement_lumiere_pas_lumiere')]
class DesordreLogementLumierePasLumiere implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
    ) {
    }

    public function process(DesordreCritere $critere, array $payload): ArrayCollection
    {
        $precisions = $this->desordreTraitementPieces->getPrecisionsPieces('desordres_logement_lumiere_pas_lumiere_pieces', $payload);

        return $precisions;
    }
}
