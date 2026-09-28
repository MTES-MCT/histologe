<?php

namespace App\Utils;

final class ExportFormat
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';
    public const CSV_SEPARATOR = ';';

    public static function getContentTypeByFormat(string $format): string
    {
        $contentType = ExportFormat::FORMAT_CSV === $format
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return $contentType;
    }
}
