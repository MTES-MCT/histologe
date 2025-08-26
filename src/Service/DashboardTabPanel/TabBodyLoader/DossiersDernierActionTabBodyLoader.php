<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersDernierActionTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS;

    public function __construct(private readonly Security $security, private readonly TabDataManagerInterface $TabDataManagerInterface)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        parent::load($tabBody);

        $data = [];
        $data['data'] = $this->TabDataManagerInterface->getDernierActionDossiers($this->tabQueryParameters);
        if ($user->isTerritoryAdmin() || $user->isSuperAdmin()) {
            $data['data_kpi'] = [
                'comptes_en_attente' => $this->TabDataManagerInterface->countUsersPendingToArchive($this->tabQueryParameters),
                'partenaires_non_notifiables' => $this->TabDataManagerInterface->countPartenairesNonNotifiables($this->tabQueryParameters),
                'partenaires_interfaces' => $this->TabDataManagerInterface->countPartenairesInterfaces($this->tabQueryParameters),
            ];
        }
        if ($user->isSuperAdmin()) {
            $data['data_interconnexion'] = $this->TabDataManagerInterface->getInterconnexions($this->tabQueryParameters);
        }
        $data['territory_id'] = $this->tabQueryParameters ? $this->tabQueryParameters->territoireId : null;

        $tabBody->setData($data);
        $tabBody->setTemplate('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig');
    }
}
