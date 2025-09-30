<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersAdresseEmailAverifierTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_ADRESSE_EMAIL_A_VERIFIER;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->tabDataManager->getDossiersAVerifierAdresseEmailAverifier($this->tabQueryParameters);
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $filters = [
            ...$tabBody->getFilters(),
            'isEmailAVerifier' => 'oui',
            'showMySignalementsOnly' => '1' === $this->tabQueryParameters->mesDossiersAverifier ? 'oui' : null,
            'sortBy' => $this->tabQueryParameters->sortBy ?? 'createdAt',
            'direction' => $this->tabQueryParameters->orderBy ?? 'DESC',
        ];
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_verifier/_body_dossier_adresse_email_a_verifier.html.twig');
    }
}
