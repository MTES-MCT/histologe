<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Enum\AffectationStatus;
use App\Repository\AffectationRepository;

class PartenaireStatisticProvider
{
    public function __construct(private AffectationRepository $affectationRepository)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerPartenaires = $this->affectationRepository->countByPartenaireFiltered($statisticsFilters);

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
                case AffectationStatus::ACCEPTED->value:
                    ++$data[$partnerName]['accepted'];
                    break;
                case AffectationStatus::REFUSED->value:
                    ++$data[$partnerName]['refused'];
                    break;
                case AffectationStatus::CLOSED->value:
                    ++$data[$partnerName]['closed'];
                    break;
                case AffectationStatus::WAIT->value:
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
