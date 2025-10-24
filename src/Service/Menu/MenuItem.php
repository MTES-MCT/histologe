<?php

namespace App\Service\Menu;

class MenuItem
{
    /** @var array<self> */
    private array $children = [];

    /**
     * @param array<mixed> $routeParameters
     */
    public function __construct(
        private readonly string $label = '',
        private readonly string $route = '',
        private readonly array $routeParameters = [],
        private readonly string $icon = '',
        private readonly string $roleGranted = '',
        private readonly string $roleNotGranted = '',
        private readonly bool $featureEnable = true,
        private readonly string $externalLink = '',
    ) {
    }

    public function addChild(self $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @return array<self>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array<mixed>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function getRoleGranted(): string
    {
        return $this->roleGranted;
    }

    public function getRoleNotGranted(): string
    {
        return $this->roleNotGranted;
    }

    public function isFeatureEnable(): bool
    {
        return $this->featureEnable;
    }

    public function getExternalLink(): string
    {
        return $this->externalLink;
    }

    public function getBaseRoute(): string
    {
        return str_replace(['_index', '_new', '_view', '_edit', '_reactiver'], '', $this->route);
    }
}
