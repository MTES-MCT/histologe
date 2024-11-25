<?php

namespace App\Dto\BOList;

class BOTable
{
    private int $page;
    private int $pages;

    public function __construct(
        private readonly ?array $headers = null,
        private readonly ?array $data = null,
        private readonly ?string $tableTitle = null,
        private readonly ?string $tableDescription = null,
        private readonly ?string $noDataLabel = null,
        private readonly ?string $rowClass = null,
        private readonly ?string $paginationSlug = null,
        private readonly ?array $paginationParams = null,
    ) {
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPages(int $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getTableTitle(): ?string
    {
        return $this->tableTitle;
    }

    public function getTableDescription(): ?string
    {
        return $this->tableDescription;
    }

    public function getNoDataLabel(): ?string
    {
        return $this->noDataLabel;
    }

    public function getRowClass(): ?string
    {
        return $this->rowClass;
    }

    public function getPaginationSlug(): ?string
    {
        return $this->paginationSlug;
    }

    public function getPaginationParams(): ?array
    {
        return $this->paginationParams;
    }
}
