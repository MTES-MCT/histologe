<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: 'desordres_logement_lumiere_pas_volets')]
class DesordreLogementLumierePasVolets implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(DesordreCritere $critere, array $payloadv): ArrayCollection
    {
        $precisions = new ArrayCollection();
        // TODO : sur le modèle de DesordreLogementLumierePasLumiere
        return $precisions;
    }
}
