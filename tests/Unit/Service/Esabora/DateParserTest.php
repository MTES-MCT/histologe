<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Interconnection\Esabora\DateParser;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    public function testParseWithDateTimeFormat(): void
    {
        $date = '03/05/2023 10:16';
        $expected = \DateTimeImmutable::createFromFormat('d/m/Y H:i', $date);
        $this->assertEquals($expected, DateParser::parse($date));
    }

    public function testParseWithDateFormat(): void
    {
        $date = '04/05/2023';
        $parsedDate = DateParser::parse($date);
        $expected = \DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $date.' 00:00:00');
        $this->assertEquals($expected, $parsedDate);
        $this->assertSame('00:00:00', $parsedDate->format('H:i:s'));
    }
}
