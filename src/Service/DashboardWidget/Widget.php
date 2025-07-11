<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class Widget
{
    #[Groups(['widget:read'])]
    private mixed $data = null;

    /**
     * @param array<int, Territory>     $territories
     * @param array<string, mixed>|null $parameters
     */
    public function __construct(
        #[Groups(['widget:read'])]
        private ?string $type = null,
        #[Groups(['widget:read'])]
        private array $territories = [],
        #[Groups(['widget:read'])]
        private ?array $parameters = null,
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array<int, Territory>
     */
    public function getTerritories(): array
    {
        return $this->territories;
    }

    /**
     * @param array<int, Territory> $territories
     */
    public function setTerritories(array $territories): self
    {
        $this->territories = $territories;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed>|null $parameters
     */
    public function setParameters(?array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }
}
