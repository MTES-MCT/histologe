<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class WidgetLoaderCollection
{
    /**
     * @var iterable<string, WidgetLoaderInterface>
     */
    private iterable $widgetLoaders;

    /**
     * @param iterable<string, WidgetLoaderInterface> $widgetLoaders
     */
    public function __construct(
        #[AutowireIterator('app.widget_loader')] iterable $widgetLoaders,
    ) {
        $this->widgetLoaders = $widgetLoaders;
    }

    public function load(Widget $widget): void
    {
        /** @var WidgetLoaderInterface $widgetLoader */
        foreach ($this->widgetLoaders as $widgetLoader) {
            if ($widgetLoader->supports($widget->getType())) {
                $widgetLoader->load($widget);
            }
        }
    }
}
