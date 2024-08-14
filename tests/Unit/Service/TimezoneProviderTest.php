<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\TimezoneProvider;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TimezoneProviderTest extends KernelTestCase
{
    use SessionHelper;

    /**
     * @throws \Exception
     *
     * @dataProvider provideTimezones
     */
    public function testGetTimezoneFrom(string $userEmail, string $timezone)
    {
        static::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.default_entity_manager');

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => $userEmail,
        ]);
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn($user);
        $timezoneProvider = new TimezoneProvider($securityMock);

        $this->assertEquals($timezone, $timezoneProvider->getTimezone());
        $this->assertEquals(new \DateTimeZone($timezone), $timezoneProvider->getDateTimezone());
    }

    public function provideTimezones(): \Generator
    {
        yield 'Fuseau horaire Martinique' => ['admin-territoire-972-01@histologe.fr', 'America/Martinique'];
        yield 'Fuseau horaire France' => ['user-62-01@histologe.fr', 'Europe/Paris'];
    }
}
