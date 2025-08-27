<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersRelanceUsagerSansReponseTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE;

    public function __construct(private readonly Security $security, private readonly TabDataManager $TabDataManager)
    {
        parent::__construct($this->security);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->TabDataManager->getDossiersRelanceSansReponse($this->tabQueryParameters);
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        if (null === $this->tabQueryParameters->orderBy) {
            $this->tabQueryParameters->orderBy = 'ASC';
        }
        $filters = [
            ...$tabBody->getFilters(),
            'relanceUsagerSansReponse' => 'oui',
        ];
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_fermer/_body_dossier_relance_usager_sans_reponse.html.twig');
    }
}
