<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManagerInterface;
use App\Service\DashboardTabPanel\TabDossier;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersFormProTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO;

    public function __construct(private readonly Security $security, private readonly TabDataManagerInterface $TabDataManagerInterface)
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
        $this->tabQueryParameters->createdFrom = TabDossier::CREATED_FROM_FORMULAIRE_PRO;

        $result = $this->TabDataManagerInterface->getNouveauxDossiersWithCount(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $this->tabQueryParameters
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);

        $filters = [
            ...$tabBody->getFilters(),
            'status' => SignalementStatus::NEED_VALIDATION->label(),
            'createdFrom' => TabDossier::CREATED_FROM_FORMULAIRE_PRO,
        ];

        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_nouveaux/_body_dossier_pro.html.twig');
    }
}
