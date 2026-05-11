<?php

namespace App\Messenger\Message;

class ListExportMessage
{
    private int $userId;
    private string $format;
    /**
     * @var array<string, mixed>
     */
    private array $filters;
    /**
     * @var array<int, string>
     */
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

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSelectedColumns(): array
    {
        return $this->selectedColumns;
    }

    /**
     * @param array<int, string> $selectedColumns
     */
    public function setSelectedColumns(array $selectedColumns): self
    {
        $this->selectedColumns = $selectedColumns;

        return $this;
    }
}
