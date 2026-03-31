<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersSansAffectationAccepteeTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_SANS_AFFECTATION_ACCEPTEE;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->tabDataManager->getDossiersAVerifierSansAffectationAcceptee($this->tabQueryParameters);
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $filters = [
            ...$tabBody->getFilters(),
            'statusAffectation' => 'aucune_affectation_acceptee',
            'status' => str_replace(' ', '_', SignalementStatus::ACTIVE->label()),
            'showMySignalementsOnly' => '1' === $this->tabQueryParameters->mesDossiersAverifier ? 'oui' : null,
            'sortBy' => 'lastSuiviAt',
            'direction' => $this->tabQueryParameters->orderBy ?? 'ASC',
        ];
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_verifier/_body_dossier_sans_affectation_acceptee.html.twig');
    }
}
