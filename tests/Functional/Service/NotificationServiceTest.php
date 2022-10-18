<?php

namespace App\Tests\Functional\Service;

use App\Entity\Suivi;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;

class NotificationServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testGetValuePropertySignalementForSubjectEmail()
    {
        $suiviRepository = $this->entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->find(1);
        $mailer = static::getContainer()->get(MailerInterface::class);
        $notificationService = new NotificationService($mailer);

        $reflection = new \ReflectionClass(\get_class($notificationService));
        $method = $reflection->getMethod('getValuePropertySignalementFrom');
        $method->setAccessible(true);
        $params = ['entity' => $suivi];
        $value = $method->invokeArgs($notificationService, [$params, 'reference']);

        $this->assertEquals('2022-1', $value);
    }
}
