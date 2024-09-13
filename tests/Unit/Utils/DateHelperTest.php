<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\DateHelper;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    /** @dataProvider provideDates */
    public function testIsValidDate($date, $format, $expected): void
    {
        $result = DateHelper::isValidDate($date, $format);
        $this->assertSame($expected, $result);
    }

    public function provideDates(): \Generator
    {
        yield 'valid date default format' => ['2023-10-05 12:34:56', 'Y-m-d H:i:s', true];
        yield 'invalid date default format' => ['2023-30-02 12:34:56', 'Y-m-d H:i:s', false];
        yield 'valid date custom format' => ['05-10-2023', 'd-m-Y', true];
        yield 'invalid date custom format' => ['31-02-2023', 'd-m-Y', false];
        yield 'empty date string' => ['', 'Y-m-d H:i:s', false];
        yield 'null date' => [null, 'Y-m-d H:i:s', false];
        yield 'non-date string' => ['not-a-date', 'Y-m-d H:i:s', false];
        yield 'partial date' => ['2023-10-05', 'Y-m-d H:i:s', false];
    }
}
