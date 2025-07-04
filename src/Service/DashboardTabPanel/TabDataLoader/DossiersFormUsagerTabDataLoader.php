<?php

namespace App\Service\DashboardTabPanel\TabDataLoader;

use App\Service\DashboardTabPanel\TabData;
use App\Service\DashboardTabPanel\TabDataLoaderInterface;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDataType;

readonly class DossiersFormUsagerTabDataLoader implements TabDataLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabData $tabData): void
    {
        $tabData->setData($this->tabDataManager->getDossiersFormUsager());
        $tabData->setTemplate('back/dashboard/tabs/data/dossiers_nouveaux/_data_dossier_usager.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabDataType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER === $type;
    }
}
