<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.widget_loader')]
interface WidgetLoaderInterface
{
    public function load(Widget $widget): void;

    public function supports(string $type): bool;
}
