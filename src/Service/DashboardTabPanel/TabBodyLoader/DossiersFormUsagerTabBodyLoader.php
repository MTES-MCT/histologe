<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoaderInterface;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;

readonly class DossiersFormUsagerTabBodyLoader implements TabBodyLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabBody $tabBody): void
    {
        $tabBody->setData($this->tabDataManager->getDossiersFormUsager());
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_nouveaux/_body_dossier_usager.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER === $type;
    }
}
