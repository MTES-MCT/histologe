<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\User;
use App\Repository\ClubEventRepository;
use App\Repository\InterventionRepository;
use App\Security\Voter\InjonctionBailleurVoter;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDossier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DossiersDernierActionTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS;

    public function __construct(
        private readonly Security $security,
        private readonly TabDataManager $tabDataManager,
        private readonly ClubEventRepository $clubEventRepository,
        private readonly InterventionRepository $interventionRepository,
        #[Autowire(env: 'FEATURE_CALENDAR')]
        private readonly bool $featureCalendar,
    ) {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        parent::load($tabBody);

        $data = [];
        $paginatedDossiers = $this->tabDataManager->getDernierActionDossiers($this->tabQueryParameters);
        $data['data'] = $paginatedDossiers['data'];
        $data['page'] = $paginatedDossiers['page'];
        $data['pages'] = ceil((int) $paginatedDossiers['total'] / TabDossier::MAX_ITEMS_DERNIERES_ACTIONS);
        if ($user->isTerritoryAdmin() || $user->isSuperAdmin()) {
            $data['data_kpi'] = [
                'comptes_en_attente' => $this->tabDataManager->countUsersPendingToArchive($this->tabQueryParameters),
                'comptes_pb_email' => $this->tabDataManager->countUsersPbEmail($this->tabQueryParameters),
                'partenaires_non_notifiables' => $this->tabDataManager->countPartenairesNonNotifiables($this->tabQueryParameters),
                'partenaires_interfaces' => $this->tabDataManager->countPartenairesInterfaces($this->tabQueryParameters),
            ];
            if ($this->security->isGranted(InjonctionBailleurVoter::INJONCTION_BAILLEUR_SEE)) {
                $data['data_kpi']['injonctions'] = $this->tabDataManager->countInjonctions($this->tabQueryParameters);
            }
            if ($user->isSuperAdmin()) {
                $data['data_kpi']['injonctions_nouveaux_messages_usager'] = $this->tabDataManager->countInjonctionsNouveauxMessages($this->tabQueryParameters, 'usager');
                $data['data_kpi']['injonctions_nouveaux_messages_bailleur'] = $this->tabDataManager->countInjonctionsNouveauxMessages($this->tabQueryParameters, 'bailleur');
            }
        }
        if ($user->isSuperAdmin()) {
            $data['data_interconnexion'] = $this->tabDataManager->getInterconnexions($this->tabQueryParameters);
        }
        $data['territory_id'] = $this->tabQueryParameters ? $this->tabQueryParameters->territoireId : null;

        $nextClubEvent = $this->clubEventRepository->findNextForUser($user);
        if ($this->featureCalendar) {
            $nextIntervention = $this->interventionRepository->findNextForUser($user, $this->tabQueryParameters?->territoireId);
            $useIntervention = $nextIntervention && (!$nextClubEvent || $nextIntervention->getScheduledAt() < $nextClubEvent->getDateEvent());
            if ($useIntervention) {
                $data['nextEvent'] = [
                    'name' => 'Visite #'.$nextIntervention->getSignalement()->getReference(),
                    'dateEvent' => $nextIntervention->getScheduledAt(),
                ];
            } elseif ($nextClubEvent) {
                $data['nextEvent'] = [
                    'name' => $nextClubEvent->getName(),
                    'dateEvent' => $nextClubEvent->getDateEvent(),
                ];
            }
        } else {
            $data['nextClubEvent'] = $nextClubEvent;
        }

        $tabBody->setData($data);
        $tabBody->setCount($paginatedDossiers['total']);
        $tabBody->setTemplate('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig');
    }
}
