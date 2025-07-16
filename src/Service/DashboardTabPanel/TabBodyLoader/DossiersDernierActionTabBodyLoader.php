<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersDernierActionTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $tabBody->setData([
            'data' => $this->tabDataManager->getDernierActionDossiers($this->tabQueryParameters),
            // TODO
            'data_kpi' => [
                'comptes_en_attente' => rand(1, 100),
                'partenaires_non_notifiables' => rand(1, 100),
            ],
            'data_interconnexion' => $this->tabDataManager->getInterconnexions($this->tabQueryParameters),
        ]);
        $tabBody->setTemplate('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig');
    }
}
