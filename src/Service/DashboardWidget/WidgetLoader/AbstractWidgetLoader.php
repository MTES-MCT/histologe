<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @deprecated This class will be removed in the next major release.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
abstract class AbstractWidgetLoader implements WidgetLoaderInterface
{
    protected ?string $widgetType = null;

    /**
     * @var array<string, mixed>
     */
    protected ?array $widgetParameter = null;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    public function load(Widget $widget): void
    {
        $this->widgetParameter = $this->parameterBag->get($widget->getType());
    }

    public function supports(string $type): bool
    {
        return $this->widgetType === $type;
    }
}
