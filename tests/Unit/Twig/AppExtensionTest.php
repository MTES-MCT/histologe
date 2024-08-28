<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\TimezoneProvider;
use App\Twig\AppExtension;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppExtensionTest extends WebTestCase
{
    private const string TWIG_DATE_FORMAT_DEFAULT = 'F j, Y H:i';
    private const string EUROPE_PARIS_TIMEZONE = 'Europe/Paris';

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

    public function testUserAvatarOrPlaceHolder(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $appExtension = $container->get(AppExtension::class);

        $user = (new User())
        ->setRoles([User::ROLES['Super Admin']]);
        $outputSpan = $appExtension->userAvatarOrPlaceHolder($user);
        $this->assertEquals('<span class="avatar-placeholder avatar-74">SA</span>', $outputSpan);

        $outputSpan = $appExtension->userAvatarOrPlaceHolder($user, 80);
        $this->assertEquals('<span class="avatar-placeholder avatar-80">SA</span>', $outputSpan);

        $user->setRoles([User::ROLES['Administrateur']]);
        $user->setTerritory((new Territory())->setZip('44'));
        $outputSpan = $appExtension->userAvatarOrPlaceHolder($user);
        $this->assertEquals('<span class="avatar-placeholder avatar-74">44</span>', $outputSpan);

        /** @var Security $security */
        $security = $this->createMock(Security::class);
        /** @var TimezoneProvider $timeZoneProvider */
        $timeZoneProvider = new TimezoneProvider($security);
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        /** @var FilesystemOperator|MockObject $fileStorage */
        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage->expects($this->exactly(2))
            ->method('fileExists')
            ->willReturn(true);

        $appExtension = new AppExtension(
            $timeZoneProvider,
            $parameterBag,
            $fileStorage,
        );

        $user->setAvatarFilename(__DIR__.'/../../files/sample.jpg');
        $outputSpan = $appExtension->userAvatarOrPlaceHolder($user);

        $this->assertStringContainsString('<img src="data:image/jpg;base64,', $outputSpan);
        $this->assertStringContainsString('alt="Avatar de l\'utilisateur" class="avatar-74">', $outputSpan);

        $outputSpan = $appExtension->userAvatarOrPlaceHolder($user, 100);

        $this->assertStringContainsString('<img src="data:image/jpg;base64,', $outputSpan);
        $this->assertStringContainsString('alt="Avatar de l\'utilisateur" class="avatar-100">', $outputSpan);
    }
}
