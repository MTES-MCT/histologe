<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class WidgetLoaderCollection
{
    private iterable $widgetLoaders;

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
