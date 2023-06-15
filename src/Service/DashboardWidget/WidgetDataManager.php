<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;

class WidgetDataManager implements WidgetDataManagerInterface
{
    public const DEFAULT_TIMEZONE = 'Europe/Paris';
    public const FORMAT_DATE_TIME = 'Y-m-d H:i';

    public function __construct(
        private SignalementRepository $signalementRepository,
        private JobEventRepository $jobEventRepository,
        private AffectationRepository $affectationRepository,
        private WidgetDataKpiBuilder $widgetDataKpiBuilder,
    ) {
    }

    public function countSignalementAcceptedNoSuivi(Territory $territory, ?array $params = null): array
    {
        return $this->signalementRepository->countSignalementAcceptedNoSuivi($territory);
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

    public function countAffectationPartner(?Territory $territory = null, ?array $params = null): array
    {
        $countAffectationPartnerList = $this->affectationRepository->countAffectationPartner($territory);

        return array_map(function ($countAffectationPartnerItem) {
            $countAffectationPartnerItem['waiting'] = (int) $countAffectationPartnerItem['waiting'];
            $countAffectationPartnerItem['refused'] = (int) $countAffectationPartnerItem['refused'];

            return $countAffectationPartnerItem;
        }, $countAffectationPartnerList);
    }

    public function findLastJobEventByInterfacageType(string $type, array $params, ?Territory $territory = null): array
    {
        $jobEvents = $this->jobEventRepository->findLastJobEventByInterfacageType($type, $params['period'], $territory);

        return array_map(function ($jobEvent) {
            $jobEvent['last_event'] = (new \DateTimeImmutable())
                ->setTimezone(new \DateTimeZone(self::DEFAULT_TIMEZONE))
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
    public function countDataKpi(?Territory $territory = null, ?array $params = null): WidgetDataKpi
    {
        return $this->widgetDataKpiBuilder
            ->createWidgetDataKpiBuilder()
            ->setTerritory($territory)
            ->withCountSignalement()
            ->withCountSuivi()
            ->withCountUser()
            ->build();
    }
}
