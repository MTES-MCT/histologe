<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Territory;

class TabData
{
    private mixed $data = null;

    public function __construct(
        private string $type,
        /** @var Territory[] */
        private array $territoires = [],
        private string $template = 'back/dashboard/tabs/data/_data_blank.html.twig',
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
