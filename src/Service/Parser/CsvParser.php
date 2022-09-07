<?php

namespace App\Service\Parser;

class CsvParser
{
    public function __construct(
        private array $options = [
            'first_line' => 1,
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
        ]
    ) {
    }

    public function parse($filepath): array
    {
        $rows = [];

        if (($fileResource = fopen($filepath, 'r')) !== false) {
            $i = 0;
            while (($row = fgetcsv($fileResource, 0, $this->options['delimiter'], $this->options['enclosure'], $this->options['escape'])) !== false) {
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
}
