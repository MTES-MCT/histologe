<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Territory;

class TabBody
{
    private mixed $data = null;

    public function __construct(
        private string $type,
        private string $template = 'back/dashboard/tabs/_body_blank.html.twig',
        /** @var Territory[] */
        private readonly array $territoires = [],
        private readonly ?TabQueryParameters $tabQueryParameters = null,
    ) {
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Territory[]
     */
    public function getTerritoires(): array
    {
        return $this->territoires;
    }

    public function getTabQueryParameters(): ?TabQueryParameters
    {
        return $this->tabQueryParameters;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }
}
