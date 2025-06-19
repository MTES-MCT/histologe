<?php

namespace App\Service\Import;

class CsvWriter
{
    private mixed $fileResource;

    /**
     * @var array<int, string>
     */
    private array $header = [];
    /**
     * @var array<string, int|string>
     */
    private array $options = [
        'first_line' => 1,
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
    ];

    public function __construct(
        private string $filepath,
    ) {
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }

        if (!empty($this->header)) {
            $this->writeRow($this->header);
        }
    }

    /**
     * @param array<int, string> $row
     */
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

    /**
     * @return array<int, string>
     */
    public function getHeader(): array
    {
        return array_filter($this->header);
    }

    /**
     * @return array<string, int|string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function close(): void
    {
        fclose($this->fileResource);
    }
}
