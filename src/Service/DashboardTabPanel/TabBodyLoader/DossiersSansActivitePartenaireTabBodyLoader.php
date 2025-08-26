<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersSansActivitePartenaireTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_SANS_ACTIVITE_PARTENAIRE;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->tabDataManager->getDossiersAVerifierSansActivitePartenaires($this->tabQueryParameters);
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $filters = [
            ...$tabBody->getFilters(),
            'isDossiersSansActivite' => 'oui',
            'showMySignalementsOnly' => '1' === $this->tabQueryParameters->mesDossiersAverifier ? 'oui' : null,
            'sortBy' => 'lastSuiviAt',
            'direction' => $this->tabQueryParameters->orderBy ?? 'ASC',
        ];
        if ($this->tabQueryParameters->partners && \count($this->tabQueryParameters->partners) > 0) {
            $filters['partenaires'] = $this->tabQueryParameters->partners;
        }
        if (null !== $this->tabQueryParameters->queryCommune && '' !== $this->tabQueryParameters->queryCommune) {
            $filters['communes[]'] = $this->tabQueryParameters->queryCommune;
        }
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_verifier/_body_dossier_sans_activite_partenaire.html.twig');
    }
}
