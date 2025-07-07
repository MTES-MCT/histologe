<?php

namespace App\Service\DashboardTabPanel;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.tab_body_loader')]
interface TabBodyLoaderInterface
{
    public function load(TabBody $tabBody): void;

    public function supports(string $type): bool;
}
