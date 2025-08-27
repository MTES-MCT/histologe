<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class DossierDemandesFermetureUsagerTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->tabDataManager->getDossiersDemandesFermetureByUsager($this->tabQueryParameters);
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        if (null === $this->tabQueryParameters->orderBy) {
            $this->tabQueryParameters->orderBy = 'ASC';
        }
        $filters = [
            ...$tabBody->getFilters(),
            'usagerAbandonProcedure' => 1,
            'status' => str_replace(' ', '_', SignalementStatus::ACTIVE->label()),
            'sortBy' => 'lastSuiviAt',
            'direction' => $this->tabQueryParameters->orderBy,
        ];
        $tabBody->setFilters($filters);

        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_fermer/_body_dossier_demande_fermeture_usager.html.twig');
    }
}
