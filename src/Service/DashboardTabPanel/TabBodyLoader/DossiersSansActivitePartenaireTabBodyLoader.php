<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoaderInterface;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;

readonly class DossiersSansActivitePartenaireTabBodyLoader implements TabBodyLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabBody $tabBody): void
    {
        $tabBody->setData($this->tabDataManager->getDossiersAVerifierSansActivitePartenaires());
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_verifier/_body_dossier_sans_activite_partenaire.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabBodyType::TAB_DATA_TYPE_SANS_ACTIVITE_PARTENAIRE === $type;
    }
}
