<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoaderInterface;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;

readonly class DossierDemandesFermetureUsagerTabBodyLoader implements TabBodyLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabBody $tabBody): void
    {
        $tabBody->setData($this->tabDataManager->getDossiersDemandesFermetureByUsager());
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_fermer/_body_dossier_demande_fermeture_usager.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER === $type;
    }
}
