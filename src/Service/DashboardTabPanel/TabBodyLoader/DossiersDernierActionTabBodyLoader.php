<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\User;
use App\Security\Voter\InjonctionBailleurVoter;
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
        /** @var User $user */
        $user = $this->security->getUser();
        parent::load($tabBody);

        $data = [];
        $data['data'] = $this->tabDataManager->getDernierActionDossiers($this->tabQueryParameters);
        if ($user->isTerritoryAdmin() || $user->isSuperAdmin()) {
            $data['data_kpi'] = [
                'comptes_en_attente' => $this->tabDataManager->countUsersPendingToArchive($this->tabQueryParameters),
                'comptes_pb_email' => $this->tabDataManager->countUsersPbEmail($this->tabQueryParameters),
                'partenaires_non_notifiables' => $this->tabDataManager->countPartenairesNonNotifiables($this->tabQueryParameters),
                'partenaires_interfaces' => $this->tabDataManager->countPartenairesInterfaces($this->tabQueryParameters),
            ];
            if ($this->security->isGranted(InjonctionBailleurVoter::SEE_INJONCTION_BAILLEUR)) {
                $data['data_kpi']['injonctions'] = $this->tabDataManager->countInjonctions($this->tabQueryParameters);
            }
        }
        if ($user->isSuperAdmin()) {
            $data['data_interconnexion'] = $this->tabDataManager->getInterconnexions($this->tabQueryParameters);
        }
        $data['territory_id'] = $this->tabQueryParameters ? $this->tabQueryParameters->territoireId : null;

        $tabBody->setData($data);
        $tabBody->setTemplate('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig');
    }
}
