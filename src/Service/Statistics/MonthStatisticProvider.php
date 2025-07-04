<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class MonthStatisticProvider
{
    /**
     * @var array<string> MONTH_NAMES
     */
    private const array MONTH_NAMES = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerMonths = $this->signalementRepository->countByMonthFiltered($statisticsFilters);

        return $this->createFullArray($countPerMonths);
    }

    /**
     * @return array<mixed>
     */
    public function getData(?Territory $territory, ?int $year): array
    {
        $countPerMonths = $this->signalementRepository->countByMonth($territory, $year, true);

        return $this->createFullArray($countPerMonths);
    }

    /**
     * @param array<mixed> $countPerMonths
     *
     * @return array<mixed>
     */
    private function createFullArray(array $countPerMonths): array
    {
        $monthsWithResults = [];
        foreach ($countPerMonths as $countPerMonth) {
            $strKey = $countPerMonth['year'].'-'.str_pad($countPerMonth['month'], 2, '0', \STR_PAD_LEFT);
            $monthsWithResults[$strKey] = $countPerMonth['count'];
        }

        $data = [];
        $previousMonth = null; // This is used to avoid blank months
        foreach ($monthsWithResults as $month => $count) {
            $dateMonth = new \DateTime($month);
            $this->fillBlankMonths($data, $previousMonth, $dateMonth);
            $strMonth = self::MONTH_NAMES[$dateMonth->format('m') - 1].' '.$dateMonth->format('Y');
            $data[$strMonth] = $count;
            $previousMonth = $dateMonth;
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     */
    private function fillBlankMonths(array &$data, ?\DateTime $previousMonth, \DateTime $currentMonth): void
    {
        if (null !== $previousMonth) {
            $shouldBeMonth = $previousMonth->format('m') + 1;
            $shouldBeYear = $previousMonth->format('Y');
            if ($shouldBeMonth > 12) {
                $shouldBeMonth = 1;
                ++$shouldBeYear;
            }
            if ($currentMonth->format('m') != $shouldBeMonth || $currentMonth->format('Y') != $shouldBeYear) {
                for ($loopYear = $shouldBeYear; $loopYear <= $currentMonth->format('Y'); ++$loopYear) {
                    $startMonth = ($loopYear == $shouldBeYear) ? $shouldBeMonth : 1;
                    $endMonth = ($loopYear < $currentMonth->format('Y')) ? 12 : $shouldBeMonth;
                    for ($loopMonth = $startMonth; $loopMonth <= $endMonth; ++$loopMonth) {
                        $strMonth = self::MONTH_NAMES[$loopMonth - 1].' '.$loopYear;
                        $data[$strMonth] = 0;
                    }
                }
            }
        }
    }
}
