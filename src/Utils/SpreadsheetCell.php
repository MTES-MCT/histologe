<?php

namespace App\Utils;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * Construit des cellules d'export neutralisées contre l'injection de formules (CSV/XLSX injection).
 */
final class SpreadsheetCell
{
    private const array FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];
    // Valeurs inoffensives (ex. placeholder "non renseigné") qui ne peuvent pas constituer une formule
    private const array SAFE_VALUES = ['-'];

    public static function fromValue(
        bool|\DateTimeInterface|float|int|string|null $value,
        string $format,
        ?Style $style = null,
    ): Cell {
        if (!\is_string($value) || '' === $value) {
            return Cell::fromValue($value, $style);
        }

        // Cell::fromValue() créerait une FormulaCell pour une chaîne commençant par "="
        if (ExportFormat::FORMAT_CSV === $format) {
            return new StringCell(self::neutralizeForCsv($value), $style);
        }

        return new StringCell($value, $style);
    }

    /**
     * @param array<bool|\DateTimeInterface|float|int|string|null> $values
     */
    public static function rowFromValues(array $values, string $format): Row
    {
        return new Row(array_map(static fn ($value) => self::fromValue($value, $format), $values));
    }

    public static function neutralizeForCsv(string $value): string
    {
        if ('' === $value
            || is_numeric($value)
            || \in_array($value, self::SAFE_VALUES, true)
            || !\in_array($value[0], self::FORMULA_TRIGGERS, true)) {
            return $value;
        }

        return "'".$value;
    }
}
