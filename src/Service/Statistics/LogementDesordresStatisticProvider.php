<?php

namespace App\Service\Statistics;

use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class LogementDesordresStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData(Territory|null $territory, int|null $year): array
    {
        $countPerLogementDesordres = $this->signalementRepository->countByDesordresCriteres(
            $territory,
            $year,
            true,
            DesordreCritereZone::LOGEMENT
        );

        return $this->createFullArray($countPerLogementDesordres);
    }

    private function createFullArray($countPerLogementDesordres): array
    {
        $data = self::initLogementDesordresPerValue();
        $i = 0;
        foreach ($countPerLogementDesordres as $countPerLogementDesordre) {
            if ($data[$i]) {
                $data[$i]['count'] = $countPerLogementDesordre['count'];
                $data[$i]['label'] = $countPerLogementDesordre['labelCritere'];
                ++$i;
            }
        }

        return $data;
    }

    private static function initLogementDesordresPerValue(): array
    {
        return [
            0 => [
                'label' => '',
                'color' => '#2F4077',
                'count' => 0,
            ],
            1 => [
                'label' => '',
                'color' => '#447049',
                'count' => 0,
            ],
            2 => [
                'label' => '',
                'color' => '#6E445A',
                'count' => 0,
            ],
            3 => [
                'label' => '',
                'color' => '#716043',
                'count' => 0,
            ],
            4 => [
                'label' => '',
                'color' => '#8D533E',
                'count' => 0,
            ],
        ];
    }
}
