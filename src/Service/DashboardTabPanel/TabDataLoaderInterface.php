<?php

namespace App\Service\DashboardTabPanel;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.tab_data_loader')]
interface TabDataLoaderInterface
{
    public function load(TabData $tabData): void;

    public function supports(string $type): bool;
}
