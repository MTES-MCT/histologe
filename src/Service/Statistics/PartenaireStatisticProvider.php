<?php

namespace App\Service\Statistics;

use App\Dto\BackStatisticsFilters;
use App\Entity\Affectation;
use App\Repository\AffectationRepository;

class PartenaireStatisticProvider
{
    public function __construct(private AffectationRepository $affectationRepository)
    {
    }

    public function getFilteredData(BackStatisticsFilters $backStatisticsFilters)
    {
        $countPerPartenaires = $this->affectationRepository->countByPartenaireFiltered($backStatisticsFilters);

        $data = [];
        foreach ($countPerPartenaires as $countPerPartenaire) {
            $partnerName = $countPerPartenaire['nom'];
            if (empty($data[$partnerName])) {
                $data[$partnerName] = [
                    'total' => 0,
                    'wait' => 0,
                    'accepted' => 0,
                    'refused' => 0,
                    'closed' => 0,
                ];
            }
            ++$data[$partnerName]['total'];
            switch ($countPerPartenaire['statut']) {
                case Affectation::STATUS_ACCEPTED:
                    ++$data[$partnerName]['accepted'];
                    break;
                case Affectation::STATUS_REFUSED:
                    ++$data[$partnerName]['refused'];
                    break;
                case Affectation::STATUS_CLOSED:
                    ++$data[$partnerName]['closed'];
                    break;
                case Affectation::STATUS_WAIT:
                default:
                    ++$data[$partnerName]['wait'];
                    break;
            }
        }

        foreach ($data as $partenaireStr => $partnerStats) {
            $totalPerPartner = $partnerStats['total'];
            $data[$partenaireStr]['accepted_percent'] = round($partnerStats['accepted'] / $totalPerPartner * 100);
            $data[$partenaireStr]['refused_percent'] = round($partnerStats['refused'] / $totalPerPartner * 100);
            $data[$partenaireStr]['closed_percent'] = round($partnerStats['closed'] / $totalPerPartner * 100);
            $data[$partenaireStr]['wait_percent'] = round($partnerStats['wait'] / $totalPerPartner * 100);
        }

        return $data;
    }
}
