<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class WidgetCardFactory
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @param array<string, mixed>|null $parameters
     */
    public function createInstance(
        string $label,
        ?int $count = null,
        ?string $route = null,
        ?array $parameters = null,
    ): WidgetCard {
        $link = null;
        if (!empty($route)) {
            $link = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new WidgetCard($label, $count, $link);
    }
}
