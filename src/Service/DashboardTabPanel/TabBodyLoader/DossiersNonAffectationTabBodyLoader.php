<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersNonAffectationTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION;

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

        $result = $this->tabDataManager->getDossierNonAffectationWithCount(
            SignalementStatus::ACTIVE,
            $this->tabQueryParameters
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);

        $filters = [
            ...$tabBody->getFilters(),
            'status' => 'en_cours',
            'partenaires[]' => 'AUCUN',
            'showWithoutAffectationOnly' => 'oui',
        ];
        $tabBody->setFilters($filters);

        $tabBody->setTemplate('back/dashboard/tabs/dossiers_nouveaux/_body_dossier_non_affectation.html.twig');
    }
}
