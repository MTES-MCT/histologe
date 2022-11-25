<?php

namespace App\Service\Statistics;

use App\Entity\Affectation;
use App\Repository\AffectationRepository;

class PartenaireStatisticProvider
{
    public function __construct(private AffectationRepository $affectationRepository)
    {
    }

    public function getFilteredData($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes)
    {
        $countPerPartenaires = $this->affectationRepository->countByPartenaireFiltered($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes, true);

        $buffer = [];
        foreach ($countPerPartenaires as $countPerPartenaire) {
            $partnerName = $countPerPartenaire['nom'];
            if (empty($buffer[$partnerName])) {
                $buffer[$partnerName] = [
                    'total' => 0,
                    'wait' => 0,
                    'accepted' => 0,
                    'refused' => 0,
                    'closed' => 0,
                ];
            }
            ++$buffer[$partnerName]['total'];
            switch ($countPerPartenaire['statut']) {
                case Affectation::STATUS_ACCEPTED:
                    ++$buffer[$partnerName]['accepted'];
                    break;
                case Affectation::STATUS_REFUSED:
                    ++$buffer[$partnerName]['refused'];
                    break;
                case Affectation::STATUS_CLOSED:
                    ++$buffer[$partnerName]['closed'];
                    break;
                case Affectation::STATUS_WAIT:
                default:
                    ++$buffer[$partnerName]['wait'];
                    break;
            }
        }

        return $buffer;
    }
}
