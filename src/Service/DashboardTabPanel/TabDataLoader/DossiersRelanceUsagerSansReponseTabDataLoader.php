<?php

namespace App\Service\DashboardTabPanel\TabDataLoader;

use App\Service\DashboardTabPanel\TabData;
use App\Service\DashboardTabPanel\TabDataLoaderInterface;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDataType;

readonly class DossiersRelanceUsagerSansReponseTabDataLoader implements TabDataLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabData $tabData): void
    {
        $tabData->setData($this->tabDataManager->getEmptyData());
        $tabData->setTemplate('back/dashboard/tabs/data/dossiers_a_fermer/_data_dossier_relance_usager_sans_reponse.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabDataType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE === $type;
    }
}
