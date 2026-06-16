<?php

namespace App\Service\Import;

class CsvParser
{
    /**
     * @var array<string, int|string>
     */
    private array $options;

    /**
     * @param array<string, int|string> $options
     */
    public function __construct(array $options = [
        'first_line' => 1,
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
    ])
    {
        $this->options = $options;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function parse(string $filepath): array
    {
        $rows = [];

        if (($fileResource = fopen($filepath, 'r')) !== false) {
            $i = 0;
            while (($row = fgetcsv(
                $fileResource,
                0,
                (string) $this->options['delimiter'],
                (string) $this->options['enclosure'],
                (string) $this->options['escape']
            )) !== false) {
                if ($i >= $this->options['first_line']) {
                    $row = array_map(static fn (?string $value) => trim((string) $value), $row);
                    $rows[] = $row;
                }
                ++$i;
            }
            fclose($fileResource);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function parseAsDict(string $filepath): array
    {
        $content = $this->getContent($filepath);
        $dataList = [];
        foreach ($content['rows'] as $row) {
            $dataItem = [];
            foreach ($row as $key => $field) {
                if (isset($content['headers'][$key])) {
                    $dataItem[$content['headers'][$key]] = $field;
                }
            }
            $dataList[] = $dataItem;
        }

        return $dataList;
    }

    /**
     * @return array<int, string>
     */
    public function getHeaders(string $filepath): array
    {
        $content = $this->getContent($filepath);

        return $content['headers'];
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function getContent(string $filepath): array
    {
        $headers = [];
        $rows = [];
        if (($fileResource = fopen($filepath, 'r')) !== false) {
            $i = 0;
            while (($row = fgetcsv(
                $fileResource,
                0,
                (string) $this->options['delimiter'],
                (string) $this->options['enclosure'],
                (string) $this->options['escape']
            )) !== false) {
                if (0 === $i) {
                    $headers = array_map(static fn ($header) => trim((string) $header), $row);
                } else {
                    $rows[] = $row;
                }
                ++$i;
            }
            fclose($fileResource);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
