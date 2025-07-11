<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Security\Voter\TabPanelVoter;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoaderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AbstractTabBodyLoader implements TabBodyLoaderInterface
{
    protected ?string $tabBodyType = null;

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
        $this->ensureAccess($tabBody);
    }

    protected function ensureAccess(TabBody $tab): void
    {
        if (!$this->security->isGranted(TabPanelVoter::VIEW_TAB_PANEL, $tab->getType())) {
            throw new AccessDeniedHttpException(sprintf('Accès interdit au panel "%s".', $tab->getType()));
        }
    }
}
