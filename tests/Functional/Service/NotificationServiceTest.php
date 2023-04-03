<?php

namespace App\Tests\Functional\Service;

use App\Entity\Suivi;
use App\Entity\Territory;
use App\Service\Mailer\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $notificationService = new NotificationService($mailer, $parameterBag);

        $reflection = new \ReflectionClass(\get_class($notificationService));
        $method = $reflection->getMethod('getValuePropertySignalementFrom');
        $method->setAccessible(true);
        $params = ['entity' => $suivi];
        $value = $method->invokeArgs($notificationService, [$params, 'reference']);

        $this->assertEquals('2022-1', $value);
    }

    public function testValidSenderForNotificationEmail()
    {
        $faker = Factory::create();

        $mailer = static::getContainer()->get(MailerInterface::class);
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $params = [
            'url' => $faker->url(),
            'code' => $faker->randomDigit(),
            'error' => $faker->text(),
        ];

        $territory = (new Territory())->setZip('01')->setName('Ain')->setIsActive(1);

        $notificationService = new NotificationService($mailer, $parameterBag);
        $mailSended = $notificationService->send(
            NotificationService::TYPE_ERROR_SIGNALEMENT,
            $parameterBag->get('admin_email'),
            $params,
            $territory
        );

        $this->assertTrue($mailSended);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHeaderSame($email, 'From', 'HISTOLOGE - AIN <notifications@histologe.beta.gouv.fr>');
        $this->assertEmailHeaderSame($email, 'To', 'support@histologe.beta.gouv.fr');
    }
}
