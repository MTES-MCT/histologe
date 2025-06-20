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
                $this->options['delimiter'],
                $this->options['enclosure'],
                $this->options['escape']
            )) !== false) {
                if ($i >= $this->options['first_line']) {
                    $row = array_map('trim', $row);
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
            foreach (str_getcsv($row) as $key => $field) {
                $dataItem[$content['headers'][$key]] = $field;
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
        $rows = explode("\n", file_get_contents($filepath));

        $headers = str_getcsv(
            array_shift($rows),
            $this->options['delimiter'],
            $this->options['enclosure'],
            $this->options['escape']
        );

        return array_map('trim', $headers);
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, string>}
     */
    public function getContent(string $filepath): array
    {
        $rows = explode("\n", file_get_contents($filepath));

        $headers = str_getcsv(
            array_shift($rows),
            $this->options['delimiter'],
            $this->options['enclosure'],
            $this->options['escape']
        );

        return [
            'headers' => array_map('trim', $headers),
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
