<?php

namespace App\Service\Statistics;

use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class BatimentDesordresStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getData(?Territory $territory, ?int $year): array
    {
        $countPerBatimentDesordres = $this->signalementRepository->countByDesordresCriteres(
            $territory,
            $year,
            DesordreCritereZone::BATIMENT
        );

        return $this->createFullArray($countPerBatimentDesordres);
    }

    /**
     * @param array<mixed> $countPerBatimentDesordres
     *
     * @return array<mixed>
     */
    private function createFullArray(array $countPerBatimentDesordres): array
    {
        $data = self::initBatimentDesordresPerValue();
        $i = 0;
        foreach ($countPerBatimentDesordres as $countPerBatimentDesordre) {
            if (isset($data[$i])) {
                $data[$i]['count'] = $countPerBatimentDesordre['count'];
                $data[$i]['label'] = $countPerBatimentDesordre['labelCritere'];
                ++$i;
            }
        }

        return $data;
    }

    /**
     * @return array<mixed>
     */
    private static function initBatimentDesordresPerValue(): array
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
