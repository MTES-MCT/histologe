<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Utils\DateHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    #[DataProvider('provideDates')]
    public function testIsValidDate(?string $date, string $format, bool $expected): void
    {
        $result = DateHelper::isValidDate($date, $format);
        $this->assertSame($expected, $result);
    }

    public static function provideDates(): \Generator
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

    #[DataProvider('provideFormatValidDate')]
    public function testFormatValidDate(
        ?string $expected,
        ?\DateTimeImmutable $dateInput,
        ?string $format,
    ): void {
        self::assertSame(
            $expected,
            DateHelper::formatValidDateInput($dateInput, $format)
        );
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function provideFormatValidDate(): \Generator
    {
        yield 'date en 1899' => [
            null,
            new \DateTimeImmutable('1899-12-31'),
            AbstractEsaboraService::FORMAT_DATE,
        ];

        yield 'date en 0000' => [
            null,
            new \DateTimeImmutable('0000-12-31'),
            AbstractEsaboraService::FORMAT_DATE,
        ];

        yield 'date future' => [
            null,
            new \DateTimeImmutable('2100-06-15'),
            AbstractEsaboraService::FORMAT_DATE,
        ];

        yield 'date cohérente' => [
            '15/06/2020',
            new \DateTimeImmutable('2020-06-15'),
            AbstractEsaboraService::FORMAT_DATE,
        ];
    }

    #[DataProvider('provideThresholds')]
    public function testTranslateDurationThresholdToFrench(string $threshold, string $expected): void
    {
        $result = DateHelper::translateDurationThresholdToFrench($threshold);
        $this->assertSame($expected, $result);
    }

    public static function provideThresholds(): \Generator
    {
        yield '1 day' => ['1 day', '1 jour'];
        yield '2 days' => ['2 days', '2 jours'];
        yield '1 week' => ['1 week', '1 semaine'];
        yield '2 weeks' => ['2 weeks', '2 semaines'];
        yield '1 month' => ['1 month', '1 mois'];
        yield '2 months' => ['2 months', '2 mois'];
        yield '1 year' => ['1 year', '1 an'];
        yield '2 years' => ['2 years', '2 ans'];
        yield '1 plop' => ['1 plop', '1 plop'];
        yield '9 plops' => ['9 plops', '9 plops'];
    }
}
