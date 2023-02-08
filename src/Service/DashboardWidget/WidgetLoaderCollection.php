<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class WidgetLoaderCollection
{
    private iterable $widgetLoaders;

    public function __construct(
        #[TaggedIterator('app.widget_loader')] iterable $widgetLoaders
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
