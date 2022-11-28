<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\SignalementRepository;
use DateTime;

class MonthStatisticProvider
{
    private const MONTH_NAMES = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(FilteredBackAnalyticsProvider $filters)
    {
        $countPerMonths = $this->signalementRepository->countByMonthFiltered($filters);

        return $this->createFullArray($countPerMonths);
    }

    public function getData(Territory|null $territory, int|null $year)
    {
        $countPerMonths = $this->signalementRepository->countByMonth($territory, $year, true);

        return $this->createFullArray($countPerMonths);
    }

    private function createFullArray($countPerMonths)
    {
        $monthsWithResults = [];
        foreach ($countPerMonths as $countPerMonth) {
            $strKey = $countPerMonth['year'].'-'.str_pad($countPerMonth['month'], 2, 0, \STR_PAD_LEFT);
            $monthsWithResults[$strKey] = $countPerMonth['count'];
        }

        $buffer = [];
        $previousMonth = null; // This is used to avoid blank months
        foreach ($monthsWithResults as $month => $count) {
            $dateMonth = new DateTime($month);
            $this->fillBlankMonths($buffer, $previousMonth, $dateMonth);
            $strMonth = self::MONTH_NAMES[$dateMonth->format('m') - 1].' '.$dateMonth->format('Y');
            $buffer[$strMonth] = $count;
            $previousMonth = $dateMonth;
        }

        return $buffer;
    }

    private function fillBlankMonths(&$buffer, $previousMonth, $currentMonth)
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
                        $buffer[$strMonth] = 0;
                    }
                }
            }
        }
    }
}
