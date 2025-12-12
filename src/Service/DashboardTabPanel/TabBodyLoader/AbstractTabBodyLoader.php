<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Security\Voter\TabPanelVoter;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AbstractTabBodyLoader implements TabBodyLoaderInterface
{
    protected ?string $tabBodyType = null;
    protected ?TabQueryParameters $tabQueryParameters = null;

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function supports(string $type): bool
    {
        return $this->tabBodyType === $type;
    }

    public function load(TabBody $tabBody): void
    {
        $this->tabQueryParameters = $tabBody->getTabQueryParameters();

        /** @var array<string, mixed> $filters */
        $filters = ['isImported' => 'oui'];
        if (null !== $this->tabQueryParameters->territoireId) {
            $filters['territoire'] = $this->tabQueryParameters->territoireId;
        }
        if (null !== $this->tabQueryParameters->sortBy && null !== $this->tabQueryParameters->orderBy) {
            $filters['sortBy'] = $this->tabQueryParameters->sortBy;
            $filters['direction'] = $this->tabQueryParameters->orderBy;
        } else {
            if (in_array($this->tabBodyType, [
                TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER,
                TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO,
                TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION,
            ])) {
                $this->tabQueryParameters->sortBy = $filters['sortBy'] = 'createdAt';
                $this->tabQueryParameters->orderBy = $filters['direction'] = 'DESC';
            }
        }
        if ($this->tabQueryParameters->partners && \count($this->tabQueryParameters->partners) > 0) {
            $filters['partenaires'] = $this->tabQueryParameters->partners;
        }
        if (null !== $this->tabQueryParameters->queryCommune && '' !== $this->tabQueryParameters->queryCommune) {
            $filters['communes[]'] = $this->tabQueryParameters->queryCommune;
        }
        $tabBody->setFilters($filters);
        $this->ensureAccess($tabBody);
    }

    protected function ensureAccess(TabBody $tab): void
    {
        if (!$this->security->isGranted(TabPanelVoter::TAB_PANEL_VIEW, $tab->getType())) {
            throw new AccessDeniedHttpException(sprintf('Accès interdit au panel "%s".', $tab->getType()));
        }
    }
}
