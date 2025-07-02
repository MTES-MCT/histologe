<?php

namespace App\Service\DashboardTabPanel\TabDataLoader;

use App\Service\DashboardTabPanel\TabData;
use App\Service\DashboardTabPanel\TabDataLoaderInterface;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDataType;

readonly class DossiersMessagesUsagersSansReponseTabDataLoader implements TabDataLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabData $tabData): void
    {
        $tabData->setData($this->tabDataManager->getEmptyData());
        $tabData->setTemplate('back/dashboard/tabs/data/dossiers_messages_usagers/_data_dossier_messages_usagers_sans_reponse.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabDataType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE === $type;
    }
}
