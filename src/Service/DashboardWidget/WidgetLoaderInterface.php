<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
#[AutoconfigureTag('app.widget_loader')]
interface WidgetLoaderInterface
{
    public function load(Widget $widget): void;

    public function supports(string $type): bool;
}
