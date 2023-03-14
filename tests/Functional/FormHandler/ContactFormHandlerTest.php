<?php

namespace App\Tests\Functional\FormHandler;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use App\FormHandler\ContactFormHandler;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class ContactFormHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ContactFormHandler $contactFormHandler;
    private SignalementRepository $signalementRepository;
    private SignalementManager $signalementManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $mailer = static::getContainer()->get(MailerInterface::class);
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $notificationService = new NotificationService($mailer, $parameterBag);
        $suiviFactory = new SuiviFactory();
        $suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->signalementManager = self::getContainer()->get(SignalementManager::class);
        $userManager = self::getContainer()->get(UserManager::class);
        $loggerInterface = self::getContainer()->get(LoggerInterface::class);
        $this->contactFormHandler = new ContactFormHandler(
            $notificationService,
            $parameterBag,
            $this->signalementRepository,
            $suiviFactory,
            $suiviManager,
            $userManager,
            $loggerInterface
        );
    }

    public function testHandleContactFormForOccupant()
    {
        $faker = Factory::create();
        $signalement = $this->signalementRepository->find(1);

        $fakeMessage = $faker->text();
        $this->contactFormHandler->handle(
            $faker->firstName(),
            $signalement->getMailOccupant(),
            $fakeMessage,
        );

        $suivis = $signalement->getSuivis();
        $suivi = $suivis->last();
        $this->assertEquals($fakeMessage.ContactFormHandler::MENTION_SENT_BY_EMAIL, $suivi->getDescription());
        $this->assertEquals(Suivi::TYPE_USAGER, $suivi->getType());
    }

    public function testHandleContactFormForDeclarant()
    {
        $faker = Factory::create();
        $signalement = $this->signalementRepository->find(1);
        $signalement->setMailDeclarant($faker->email());
        $this->signalementManager->save($signalement);

        $fakeMessage = $faker->text();
        $this->contactFormHandler->handle(
            $faker->firstName(),
            $signalement->getMailDeclarant(),
            $fakeMessage,
        );

        $suivis = $signalement->getSuivis();
        $suivi = $suivis->last();
        $this->assertEquals($fakeMessage.ContactFormHandler::MENTION_SENT_BY_EMAIL, $suivi->getDescription());
        $this->assertEquals(Suivi::TYPE_USAGER, $suivi->getType());
    }
}
