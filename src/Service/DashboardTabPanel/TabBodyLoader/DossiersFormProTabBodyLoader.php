<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\CreationSource;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersFormProTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO;

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
        /** @var User $user */
        $user = $this->security->getUser();
        parent::load($tabBody);
        if ($user->isSuperAdmin()) {
            $this->tabQueryParameters->createdFrom = CreationSource::CREATED_FROM_FORMULAIRE_PRO;
        } else {
            $this->tabQueryParameters->createdFrom = CreationSource::CREATED_FROM_FORMULAIRE_PRO_BO_API;
        }

        $result = $this->tabDataManager->getNouveauxDossiersWithCount(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $this->tabQueryParameters,
            territoires: $tabBody->getTerritoires(),
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);

        $filters = [
            ...$tabBody->getFilters(),
            'status' => SignalementStatus::NEED_VALIDATION->label(),
            'createdFrom' => $user->isSuperAdmin() ? CreationSource::CREATED_FROM_FORMULAIRE_PRO : CreationSource::CREATED_FROM_FORMULAIRE_PRO_BO_API,
        ];

        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_nouveaux/_body_dossier_pro.html.twig');
    }
}
