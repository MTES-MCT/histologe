<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Interconnection\Esabora\DateParser;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testParseWithDateTimeFormat(): void
    {
        $date = '03/05/2023 10:16';
        $this->assertEquals(
            new \DateTimeImmutable('2023-05-03 08:16', new \DateTimeZone('UTC')),
            DateParser::parse($date, 'Europe/Paris'));
    }

    /**
     * @throws \Exception
     */
    public function testParseWithDateFormat(): void
    {
        $date = '04/05/2023';
        $parsedDate = DateParser::parse($date, 'Europe/Paris');
        $this->assertEquals(new \DateTimeImmutable('2023-05-04 00:00', new \DateTimeZone('UTC')),
            $parsedDate);

        $this->assertSame('00:00:00', $parsedDate->format('H:i:s'));
    }
}
