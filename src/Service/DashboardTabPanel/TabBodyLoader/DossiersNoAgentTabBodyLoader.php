<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
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
        /** @var User $user */
        $user = $this->security->getUser();

        if (null === $this->tabQueryParameters->territoireId) {
            $this->tabQueryParameters->partenairesId = $user->getPartners()
                ->map(fn ($partner) => $partner->getId())
                ->toArray();
        } else {
            $territoire = $tabBody->getTerritoires()[$this->tabQueryParameters->territoireId];
            $partner = $user->getPartnerInTerritory($territoire);
            $this->tabQueryParameters->partenairesId = [$partner->getId()];
        }

        $result = $this->tabDataManager->getDossiersNoAgentWithCount(
            affectationStatus: AffectationStatus::ACCEPTED,
            tabQueryParameters: $this->tabQueryParameters
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);

        $filters = [
            ...$tabBody->getFilters(),
            'status' => SignalementStatus::NEED_VALIDATION->label(),
        ];
        $tabBody->setFilters($filters);

        $tabBody->setTemplate('back/dashboard/tabs/dossiers_nouveaux/_body_dossier_no_agent.html.twig');
    }
}
