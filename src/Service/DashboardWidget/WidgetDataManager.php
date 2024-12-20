<?php

namespace App\Service\DashboardWidget;

use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Service\TimezoneProvider;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;

class WidgetDataManager implements WidgetDataManagerInterface
{
    public const FORMAT_DATE_TIME = 'Y-m-d H:i';

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly JobEventRepository $jobEventRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly WidgetDataKpiBuilder $widgetDataKpiBuilder,
    ) {
    }

    public function countSignalementAcceptedNoSuivi(array $territories, ?array $params = null): array
    {
        return $this->signalementRepository->countSignalementAcceptedNoSuivi($territories);
    }

    /**
     * @throws Exception
     */
    public function countSignalementsByTerritory(?array $params = null): array
    {
        $countSignalementTerritoryList = $this->signalementRepository->countSignalementTerritory();

        return array_map(function ($countSignalementTerritoryItem) {
            $countSignalementTerritoryItem['new'] = (int) $countSignalementTerritoryItem['new'];
            $countSignalementTerritoryItem['no_affected'] = (int) $countSignalementTerritoryItem['no_affected'];

            return $countSignalementTerritoryItem;
        }, $countSignalementTerritoryList);
    }

    public function countAffectationPartner(array $territories, ?array $params = null): array
    {
        $countAffectationPartnerList = $this->affectationRepository->countAffectationPartner($territories);

        return array_map(function ($countAffectationPartnerItem) {
            $countAffectationPartnerItem['waiting'] = (int) $countAffectationPartnerItem['waiting'];
            $countAffectationPartnerItem['refused'] = (int) $countAffectationPartnerItem['refused'];

            return $countAffectationPartnerItem;
        }, $countAffectationPartnerList);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function findLastJobEventByInterfacageType(string $type, array $params, array $territories): array
    {
        $jobEvents = $this->jobEventRepository->findLastJobEventByInterfacageType($type, $params['period'], $territories);

        return array_map(/**
         * @throws \Exception
         */ function ($jobEvent) use ($territories) {
            /** @var \DateTimeImmutable $createdAt */
            $createdAt = $jobEvent['createdAt'];
            $timezone = count($territories) ? reset($territories)->getTimezone() : TimezoneProvider::TIMEZONE_EUROPE_PARIS;
            $jobEvent['last_event'] = $createdAt
                ->setTimezone(new \DateTimeZone($timezone))
                ->format(self::FORMAT_DATE_TIME);

            return $jobEvent;
        }, $jobEvents);
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function countDataKpi(array $territories, ?array $params = null): WidgetDataKpi
    {
        return $this->widgetDataKpiBuilder
            ->createWidgetDataKpiBuilder()
            ->setTerritories($territories)
            ->withCountSignalement()
            ->withCountSuivi()
            ->withCountUser()
            ->build();
    }
}
