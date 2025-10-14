<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersActiviteRecenteTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_ACTIVITE_RECENTE;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);

        $result = $this->tabDataManager->getDossiersActiviteRecente(
            $this->tabQueryParameters
        );
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $filters = [
            ...$tabBody->getFilters(),
            'isActiviteRecente' => 'oui',
            'showMySignalementsOnly' => '1' === $this->tabQueryParameters->mesDossiersActiviteRecente ? 'oui' : null,
            'sortBy' => 'lastSuiviAt',
            'direction' => $this->tabQueryParameters->orderBy ?? 'DESC',
        ];
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_activite_recente/_body_dossier_activite_recente.html.twig');
    }
}
