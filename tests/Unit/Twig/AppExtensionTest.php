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

    public function testGetFilters(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $appExtension = $container->get(AppExtension::class);

        $filters = $appExtension->getFilters();

        $filterNames = array_map(fn ($f) => $f->getName(), $filters);

        $expectedFilters = [
            'date',
            'status_to_css',
            'signalement_lien_declarant_occupant',
            'image64',
            'truncate_filename',
            'clean_tagged_text',
            'phone',
            'badge_class',
            'badge_relance_class',
        ];

        $this->assertEqualsCanonicalizing($expectedFilters, $filterNames);
    }

    /**
     * @dataProvider provideBadgeClass
     */
    public function testGetBadgeClass(?int $days, string $expected): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $appExtension = $container->get(AppExtension::class);

        $this->assertSame($expected, $appExtension->getBadgeClass($days));
    }

    public function provideBadgeClass(): \Generator
    {
        yield 'More than 365 days' => [366, 'fr-badge--error'];
        yield 'Exactly 365 days' => [365, 'fr-badge--warning'];
        yield 'Between 181 and 364 days' => [200, 'fr-badge--warning'];
        yield 'Null days' => [null, 'fr-badge--info'];
        yield 'Between 91 and 180 days' => [100, 'fr-badge--new'];
        yield 'Less than 91 days' => [30, 'fr-badge--success'];
    }

    /**
     * @dataProvider provideRelanceBadgeClass
     */
    public function testGetRelanceBadgeClass(int $count, string $expected): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $appExtension = $container->get(AppExtension::class);

        $this->assertSame($expected, $appExtension->getRelanceBadgeClass($count));
    }

    public function provideRelanceBadgeClass(): \Generator
    {
        yield 'More than 10 relances' => [11, 'fr-badge--error'];
        yield 'Exactly 10 relances' => [10, 'fr-badge--warning'];
        yield 'Between 4 and 9 relances' => [5, 'fr-badge--warning'];
        yield 'Less than 4 relances' => [2, 'fr-badge--new'];
        yield 'Zero relance' => [0, 'fr-badge--new'];
    }
}
