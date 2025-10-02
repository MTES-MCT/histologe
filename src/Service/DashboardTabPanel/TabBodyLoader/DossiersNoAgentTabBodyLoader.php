<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\AffectationStatus;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersNoAgentTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NO_AGENT;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);

        $result = $this->tabDataManager->getDossiersNoAgentWithCount(
            affectationStatus: AffectationStatus::ACCEPTED,
            tabQueryParameters: $this->tabQueryParameters,
            territoires: $tabBody->getTerritoires(),
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);

        $filters = [
            ...$tabBody->getFilters(),
            'isDossiersSansAgent' => 'oui',
        ];
        $tabBody->setFilters($filters);

        $tabBody->setTemplate('back/dashboard/tabs/dossiers_nouveaux/_body_dossier_no_agent.html.twig');
    }
}
