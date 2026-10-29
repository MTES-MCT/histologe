<?php

namespace App\Service\Import;

class CsvParser
{
    private const string REGEX_UTF8_BOM = '/^\xEF\xBB\xBF/';

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
                    $row = array_map(fn (?string $value) => $this->cleanValue($value), $row);
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
        $nbColonnes = \count($content['headers']);
        foreach ($content['rows'] as $row) {
            $dataItem = [];
            if (\count($row) !== $nbColonnes) {
                continue;
            }
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
            $headerLine = max(0, (int) $this->options['first_line'] - 1);
            while (($row = fgetcsv(
                $fileResource,
                0,
                (string) $this->options['delimiter'],
                (string) $this->options['enclosure'],
                (string) $this->options['escape']
            )) !== false) {
                if ($i === $headerLine) {
                    $headers = array_map(fn ($header) => $this->cleanValue((string) $header), $row);
                } elseif ($i > $headerLine) {
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

    private function cleanValue(?string $value): string
    {
        return trim((string) preg_replace(self::REGEX_UTF8_BOM, '', (string) $value));
    }

    /**
     * @return array<string, int|string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setFirstLine(int $firstLine): self
    {
        $this->options['first_line'] = $firstLine;

        return $this;
    }

    public function autoDetectDelimiter(string $filepath): self
    {
        $delimiters = [',', ';'];
        $delimiterDetected = ',';
        $maxFields = -1;

        if (($fileResource = fopen($filepath, 'r')) !== false) {
            $firstLine = fgets($fileResource);
            fclose($fileResource);

            if ($firstLine) {
                foreach ($delimiters as $delimiter) {
                    $fields = str_getcsv(
                        $firstLine,
                        $delimiter,
                        (string) $this->options['enclosure'],
                        (string) $this->options['escape']
                    );
                    $count = \count($fields);
                    if ($count > $maxFields) {
                        $maxFields = $count;
                        $delimiterDetected = $delimiter;
                    }
                }
            }
        }

        $this->options['delimiter'] = $delimiterDetected;

        return $this;
    }

    /**
     * @param string[] $expectedHeaders
     */
    public function autoDetectFirstLine(string $filepath, array $expectedHeaders = [], int $maxSearchLines = 2): self
    {
        if (empty($expectedHeaders)) {
            return $this;
        }

        $bestLine = 1;
        $minMissing = null;

        for ($line = 1; $line <= $maxSearchLines; ++$line) {
            $this->setFirstLine($line);
            $headers = $this->getHeaders($filepath);
            if (empty($headers)) {
                continue;
            }
            $missing = \count(array_diff($expectedHeaders, $headers));
            if (0 === $missing) {
                return $this;
            }
            if (null === $minMissing || $missing < $minMissing) {
                $minMissing = $missing;
                $bestLine = $line;
            }
        }

        $this->setFirstLine($bestLine);

        return $this;
    }
}
