<?php

namespace App\Tests\Functional\Service;

use App\Entity\Territory;
use App\Service\Mailer\NotificationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class NotificationServiceTest extends KernelTestCase
{
//    private EntityManagerInterface $entityManager;
//
//    protected function setUp(): void
//    {
//        $kernel = self::bootKernel();
//        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
//    }
//
//    public function testValidSenderForNotificationEmail()
//    {
//        $faker = Factory::create();
//
//        $mailer = static::getContainer()->get(MailerInterface::class);
//        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
//        $params = [
//            'url' => $faker->url(),
//            'code' => $faker->randomDigit(),
//            'error' => $faker->text(),
//        ];
//
//        $territory = (new Territory())->setZip('01')->setName('Ain')->setIsActive(1);
//
//        $notificationService = new NotificationMailer($mailer, $parameterBag);
//        $mailSended = $notificationService->send(
//            NotificationMailer::TYPE_ERROR_SIGNALEMENT,
//            $parameterBag->get('admin_email'),
//            $params,
//            $territory
//        );
//
//        $this->assertTrue($mailSended);
//        $this->assertEmailCount(1);
//        $email = $this->getMailerMessage();
//        $this->assertEmailHeaderSame($email, 'From', 'HISTOLOGE - AIN <notifications@histologe.beta.gouv.fr>');
//        $this->assertEmailHeaderSame($email, 'To', 'support@histologe.beta.gouv.fr');
//    }
}
