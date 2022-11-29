<?php

namespace App\Service\Statistics;

use App\Dto\BackStatisticsFilters;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use DateTime;

class MonthStatisticProvider
{
    private const MONTH_NAMES = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(BackStatisticsFilters $backStatisticsFilters): array
    {
        $countPerMonths = $this->signalementRepository->countByMonthFiltered($backStatisticsFilters);

        return $this->createFullArray($countPerMonths);
    }

    public function getData(Territory|null $territory, int|null $year): array
    {
        $countPerMonths = $this->signalementRepository->countByMonth($territory, $year, true);

        return $this->createFullArray($countPerMonths);
    }

    private function createFullArray($countPerMonths): array
    {
        $monthsWithResults = [];
        foreach ($countPerMonths as $countPerMonth) {
            $strKey = $countPerMonth['year'].'-'.str_pad($countPerMonth['month'], 2, 0, \STR_PAD_LEFT);
            $monthsWithResults[$strKey] = $countPerMonth['count'];
        }

        $data = [];
        $previousMonth = null; // This is used to avoid blank months
        foreach ($monthsWithResults as $month => $count) {
            $dateMonth = new DateTime($month);
            $this->fillBlankMonths($data, $previousMonth, $dateMonth);
            $strMonth = self::MONTH_NAMES[$dateMonth->format('m') - 1].' '.$dateMonth->format('Y');
            $data[$strMonth] = $count;
            $previousMonth = $dateMonth;
        }

        return $data;
    }

    private function fillBlankMonths(&$data, $previousMonth, $currentMonth)
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
