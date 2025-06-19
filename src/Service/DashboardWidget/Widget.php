<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\Serializer\Attribute\Groups;

class Widget
{
    #[Groups(['widget:read'])]
    private mixed $data = null;

    /**
     * @var array<int, mixed>
     */
    private array $territories = [];
    /**
     * @var array<string, mixed>|null
     */
    private ?array $parameters = null;

    public function __construct(
        #[Groups(['widget:read'])]
        private ?string $type = null,
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
     * @return array<int, mixed>
     */
    public function getTerritories(): array
    {
        return $this->territories;
    }

    /**
     * @param array<int, mixed> $territories
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
