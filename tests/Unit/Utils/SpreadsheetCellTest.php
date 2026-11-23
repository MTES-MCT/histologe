<?php

namespace App\Tests\Unit\Utils;

use App\Utils\ExportFormat;
use App\Utils\SpreadsheetCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpreadsheetCellTest extends TestCase
{
    #[DataProvider('provideCsvValues')]
    public function testNeutralizeForCsv(string $value, string $expected): void
    {
        $this->assertSame($expected, SpreadsheetCell::neutralizeForCsv($value));
    }

    public static function provideCsvValues(): \Generator
    {
        yield 'equal' => ['=HYPERLINK("http://evil.test","clic")', '\'=HYPERLINK("http://evil.test","clic")'];
        yield 'plus' => ['+1+cmd|calc', '\'+1+cmd|calc'];
        yield 'minus' => ['-2+3', '\'-2+3'];
        yield 'at' => ['@SUM(A1)', '\'@SUM(A1)'];
        yield 'tab' => ["\t=1+1", "'\t=1+1"];
        yield 'carriage return' => ["\r=1+1", "'\r=1+1"];
        yield 'plain text' => ['Dupont', 'Dupont'];
        yield 'negative number' => ['-61.5340', '-61.5340'];
        yield 'phone' => ['+33612345678', '+33612345678'];
        yield 'empty' => ['', ''];
        yield 'dash placeholder' => ['-', '-'];
        yield 'dash with spaces' => ['- ', '\'- '];
    }

    public function testFromValueNeverCreatesFormulaInXlsx(): void
    {
        $cell = SpreadsheetCell::fromValue('=1+1', ExportFormat::FORMAT_XLSX);

        $this->assertNotInstanceOf(FormulaCell::class, $cell);
        $this->assertInstanceOf(StringCell::class, $cell);
        $this->assertSame('=1+1', $cell->getValue());
    }

    public function testFromValuePrefixesInCsv(): void
    {
        $cell = SpreadsheetCell::fromValue('=1+1', ExportFormat::FORMAT_CSV);

        $this->assertInstanceOf(StringCell::class, $cell);
        $this->assertSame("'=1+1", $cell->getValue());
    }

    public function testFromValueKeepsNonStringTypes(): void
    {
        $this->assertInstanceOf(NumericCell::class, SpreadsheetCell::fromValue(-3, ExportFormat::FORMAT_CSV));
    }
}
