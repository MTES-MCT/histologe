<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WidgetCardFactory
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function createInstance(
        string $label,
        ?int $count = null,
        ?string $route = null,
        ?array $parameters = null
    ): WidgetCard {
        $link = null;
        if (!empty($route)) {
            $link = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new WidgetCard($label, $count, $link);
    }
}
