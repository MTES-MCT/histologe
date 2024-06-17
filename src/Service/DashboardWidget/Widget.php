<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;
use Symfony\Component\Serializer\Attribute\Groups;

class Widget
{
    #[Groups(['widget:read'])]
    private mixed $data = null;

    public function __construct(
        #[Groups(['widget:read'])]
        private ?string $type = null,
        #[Groups(['widget:read'])]
        private ?Territory $territory = null,
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

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): self
    {
        $this->territory = $territory;

        return $this;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

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
