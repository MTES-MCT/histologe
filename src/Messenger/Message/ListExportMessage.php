<?php

namespace App\Messenger\Message;

class ListExportMessage
{
    private int $userId;
    private string $format;
    private array $filters;
    private array $selectedColumns;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getSelectedColumns(): array
    {
        return $this->selectedColumns;
    }

    public function setSelectedColumns(array $selectedColumns): self
    {
        $this->selectedColumns = $selectedColumns;

        return $this;
    }
}
