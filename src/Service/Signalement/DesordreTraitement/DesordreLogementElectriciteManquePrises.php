<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: 'desordres_logement_electricite_manque_prises')]
class DesordreLogementElectriciteManquePrises implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(DesordreCritere $critere, array $payload): ArrayCollection
    {
        $precisions = new ArrayCollection();
        if (isset($payload['desordres_logement_electricite_manque_prises_details_multiprises'])) {
            if ('oui' === $payload['desordres_logement_electricite_manque_prises_details_multiprises']) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_logement_electricite_manque_prises_details_multiprises_oui']
                );
            } else {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_logement_electricite_manque_prises_details_multiprise_non']
                );
            }
            $precisions->add($precision);
        }
        // TODO : à voir avec Mathilde, dans l'algo on ne prend pas en compte les pièces dans lesquels on manque de prises desordres_logement_electricite_manque_prises_details_pieces

        return $precisions;
    }
}
