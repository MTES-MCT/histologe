<?php

namespace App\Service\Statistics;

use App\Entity\Affectation;
use App\Repository\AffectationRepository;

class PartenaireStatisticProvider
{
    public function __construct(private AffectationRepository $affectationRepository)
    {
    }

    public function getFilteredData(FilteredBackAnalyticsProvider $filters)
    {
        $countPerPartenaires = $this->affectationRepository->countByPartenaireFiltered($filters);

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

        foreach ($buffer as $partenaireStr => $partnerStats) {
            $totalPerPartner = $partnerStats['total'];
            $buffer[$partenaireStr]['accepted_percent'] = round($partnerStats['accepted'] / $totalPerPartner * 100);
            $buffer[$partenaireStr]['refused_percent'] = round($partnerStats['refused'] / $totalPerPartner * 100);
            $buffer[$partenaireStr]['closed_percent'] = round($partnerStats['closed'] / $totalPerPartner * 100);
            $buffer[$partenaireStr]['wait_percent'] = round($partnerStats['wait'] / $totalPerPartner * 100);
        }

        return $buffer;
    }
}
