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
    ) {
    }

    public function process(DesordreCritere $critere, array $payload): ArrayCollection
    {
        $precisions = new ArrayCollection();

        // TODO : externaliser ce traitement car sera utilisé par plusieurs critères
        if (isset($payload['desordres_logement_lumiere_pas_lumiere_pieces'])
        && 1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces']) {
            if ('piece_unique' === $payload['composition_logement_piece_unique']
            || (1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces_cuisine']
                && 1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces_piece_a_vivre']
                && 1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces_salle_de_bain'])
            ) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_logement_lumiere_pas_lumiere_pieces_tout']
                );
                $precisions->add($precision);
            } else {
                if (1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces_cuisine']) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => 'desordres_logement_lumiere_pas_lumiere_pieces_cuisine']
                    );
                    $precisions->add($precision);
                }
                if (1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces_piece_a_vivre']) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => 'desordres_logement_lumiere_pas_lumiere_pieces_piece_a_vivre']
                    );
                    $precisions->add($precision);
                }
                if (1 === $payload['desordres_logement_lumiere_pas_lumiere_pieces_salle_de_bain']) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => 'desordres_logement_lumiere_pas_lumiere_pieces_salle_de_bain']
                    );
                    $precisions->add($precision);
                }
            }
        }

        return $precisions;
    }
}
