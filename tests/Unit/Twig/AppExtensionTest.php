<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppExtensionTest extends WebTestCase
{
    /**
     * @dataProvider provideData
     */
    public function testCustomDateFiler($inputDate, $expectedOutputDate, $format = 'F j, Y H:i', $timezone = null): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $appExtension = $container->get(AppExtension::class);

        $outputDate = $appExtension->customDateFilter($inputDate, $format, $timezone);

        $this->assertEquals($expectedOutputDate, $outputDate);
    }

    /**
     * @throws \Exception
     */
    public function provideData(): \Generator
    {
        yield 'DateTimeImmutable, no timezone so Europe/Paris by default' => [
            new \DateTimeImmutable('2024-07-08 09:00:00'),
            'July 8, 2024 11:00',
        ];

        yield 'DateTimeImmutable,  no timezone with America Cayenne' => [
            new \DateTimeImmutable('2024-07-08 09:00:00'),
            'July 8, 2024 06:00',
            'F j, Y H:i',
            'America/Cayenne',
        ];

        yield 'DateTime, specific timezone' => [
            new \DateTime('2024-07-08 09:00:00', new \DateTimeZone('Europe/Paris')),
            'July 8, 2024 09:00',
            'F j, Y H:i',
            'Europe/Paris',
        ];

        yield 'DateTime, no timezone so Europe/Paris by default' => [
            new \DateTime('2024-07-08 09:00:00'),
            'July 8, 2024 11:00',
            'F j, Y H:i',
            'Europe/Paris',
        ];

        yield 'String date, paris timezone' => [
            '2024-07-08 09:00:00',
            'July 8, 2024 11:00', // expected output in Europe/Paris
            'F j, Y H:i',
            'Europe/Paris',
        ];

        yield 'String date, cayenne timezone' => [
            '2024-07-08 09:00:00',
            'July 8, 2024 06:00', // expected output in America/Cayenne
            'F j, Y H:i',
            'America/Cayenne',
        ];

        yield 'String date, default timezone (UTC)' => [
            '2024-07-08 09:00:00',
            'July 8, 2024 11:00',
            'F j, Y H:i',
        ];

        yield 'Timestamp, specific timezone' => [
            '1719926400', // timestamp for July 2, 2024 13:20
            'July 2, 2024 15:20', // expected output in Europe/Paris
            'F j, Y H:i',
            'Europe/Paris',
        ];

        yield 'Timestamp, default timezone (UTC)' => [
            '1719926400', // timestamp for July 2, 2024 09:00:00 UTC
            'July 2, 2024 13:20', // expected output in UTC
            'F j, Y H:i',
            'UTC',
        ];
    }
}
