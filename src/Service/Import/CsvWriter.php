<?php

namespace App\Service\Import;

class CsvWriter
{
    private mixed $fileResource;

    public function __construct(
        private string $filepath,
        private array $header = [],
        private array $options = [
            'first_line' => 1,
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
        ]
    ) {
        if (!empty($this->header)) {
            $this->writeRow($this->header);
        }
    }

    public function writeRow(array $row): void
    {
        $this->fileResource = fopen($this->filepath, 'a');
        fputcsv(
            $this->fileResource,
            $row,
            $this->options['delimiter'],
            $this->options['enclosure'],
            $this->options['escape'],
        );
    }

    public function getHeader(): array
    {
        return array_filter($this->header);
    }

    public function close(): void
    {
        fclose($this->fileResource);
    }
}
