<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreTraitementNuisibles implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = new ArrayCollection();

        if (isset($payload[$slug.'_details_date'])) {
            if ('before_movein' === $payload[$slug.'_details_date']) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slug.'_details_date_before_movein']
                );
            } else {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slug.'_details_date_after_movein']
                );
            }
            $precisions->add($precision);
        }

        return $precisions;
    }
}
