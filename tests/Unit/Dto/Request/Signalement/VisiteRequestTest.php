<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\VisiteRequest;
use PHPUnit\Framework\TestCase;

class VisiteRequestTest extends TestCase
{
    /**
     * @dataProvider visiteRequestDataProvider
     *
     * @throws \Exception
     */
    public function testDatesVisiteRequestDTO(string $date, string $time, string $timezone, string $expectedLocale, string $expectedUTC): void
    {
        $visiteRequest = new VisiteRequest(
            date: $date,
            time: $time,
            timezone: $timezone,
        );

        $this->assertEquals($expectedLocale, $visiteRequest->getDateTimeLocale());
        $this->assertEquals($expectedUTC, $visiteRequest->getDateTimeUTC());
    }

    public function visiteRequestDataProvider(): \Generator
    {
        yield 'France' => [
            'date' => '2024-08-13',
            'time' => '12:00:00',
            'timezone' => 'Europe/Paris',
            'expectedLocale' => '2024-08-13 12:00:00',
            'expectedUTC' => '2024-08-13 10:00:00',
        ];

        yield 'Martinique' => [
            'date' => '2024-08-13',
            'time' => '12:00:00',
            'timezone' => 'America/Martinique',
            'expectedLocale' => '2024-08-13 12:00:00',
            'expectedUTC' => '2024-08-13 16:00:00',
        ];
    }
}
