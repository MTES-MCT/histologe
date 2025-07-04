<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppExtensionTest extends WebTestCase
{
    private const string TWIG_DATE_FORMAT_DEFAULT = 'F j, Y H:i';
    private const string EUROPE_PARIS_TIMEZONE = 'Europe/Paris';

    /**
     * @dataProvider provideData
     */
    public function testCustomDateFiler(\DateTimeInterface|string $inputDate, string $expectedOutputDate, ?string $format = 'F j, Y H:i', ?string $timezone = null): void
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
            self::TWIG_DATE_FORMAT_DEFAULT,
            'America/Cayenne',
        ];

        yield 'DateTime, specific timezone' => [
            new \DateTime('2024-07-08 09:00:00', new \DateTimeZone(self::EUROPE_PARIS_TIMEZONE)),
            'July 8, 2024 09:00',
            self::TWIG_DATE_FORMAT_DEFAULT,
            self::EUROPE_PARIS_TIMEZONE,
        ];

        yield 'DateTime, no timezone so Europe/Paris by default' => [
            new \DateTime('2024-07-08 09:00:00'),
            'July 8, 2024 11:00',
            self::TWIG_DATE_FORMAT_DEFAULT,
            self::EUROPE_PARIS_TIMEZONE,
        ];

        yield 'String date, paris timezone' => [
            '2024-07-08 09:00:00',
            'July 8, 2024 11:00', // expected output in Europe/Paris
            self::TWIG_DATE_FORMAT_DEFAULT,
            self::EUROPE_PARIS_TIMEZONE,
        ];

        yield 'String date, cayenne timezone' => [
            '2024-07-08 09:00:00',
            'July 8, 2024 06:00', // expected output in America/Cayenne
            self::TWIG_DATE_FORMAT_DEFAULT,
            'America/Cayenne',
        ];

        yield 'String date, default timezone (UTC)' => [
            '2024-07-08 09:00:00',
            'July 8, 2024 11:00',
            self::TWIG_DATE_FORMAT_DEFAULT,
        ];

        yield 'Timestamp, specific timezone' => [
            '1719926400', // timestamp for July 2, 2024 13:20
            'July 2, 2024 15:20', // expected output in Europe/Paris
            self::TWIG_DATE_FORMAT_DEFAULT,
            self::EUROPE_PARIS_TIMEZONE,
        ];

        yield 'Timestamp, default timezone (UTC)' => [
            '1719926400', // timestamp for July 2, 2024 09:00:00 UTC
            'July 2, 2024 13:20', // expected output in UTC
            self::TWIG_DATE_FORMAT_DEFAULT,
            'UTC',
        ];
    }

    /**
     * @dataProvider provideDataPhone
     */
    public function testFormatPhone(?string $inputPhone, ?string $expectedOutputPhone): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $appExtension = $container->get(AppExtension::class);

        $outputPhone = $appExtension->formatPhone($inputPhone);

        $this->assertEquals($expectedOutputPhone, $outputPhone);
    }

    /**
     * @throws \Exception
     */
    public function provideDataPhone(): \Generator
    {
        yield 'No phone' => [
            null,
            '',
        ];
        yield 'French decoded phone' => [
            '+33612345678',
            '+33 6 12 34 56 78',
        ];
        yield 'French phone' => [
            '0612345678',
            '0612345678',
        ];
        yield 'Other phone' => [
            '+31612345678',
            '+31612345678',
        ];
    }
}
