<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: 'desordres_logement_nuisibles_cafards')]
class DesordreLogementNuisiblesCafards implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(DesordreCritere $critere, array $payload): ArrayCollection
    {
        $precisions = new ArrayCollection();
        if (isset($payload['desordres_logement_nuisibles_cafards_details_date'])) {
            if ('before_movein' === $payload['desordres_logement_nuisibles_cafards_details_date']) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_logement_nuisibles_cafards_details_date_before_movein']
                );
            } else {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => 'desordres_logement_nuisibles_cafards_details_date_after_movein']
                );
            }
            $precisions->add($precision);
        }

        return $precisions;
    }
}
