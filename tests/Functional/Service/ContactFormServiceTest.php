<?php

namespace App\Tests\Functional\Service;

use App\Entity\Signalement;
use App\Factory\SuiviFactory;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\ContactFormService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class ContactFormServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ContactFormService $contactFormService;
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
        $this->contactFormService = new ContactFormService(
            $notificationService,
            $parameterBag,
            $this->signalementRepository,
            $suiviFactory,
            $suiviManager,
            $userManager
        );
    }

    public function testDispatchContactFormForOccupant()
    {
        $faker = Factory::create();
        $signalement = $this->signalementRepository->find(1);

        $fakeMessage = $faker->text();
        $this->contactFormService->dispatch(
            $faker->firstName(),
            $signalement->getMailOccupant(),
            $fakeMessage,
        );

        $suivis = $signalement->getSuivis();
        $suivi = $suivis[\count($suivis) - 1];
        $this->assertEquals($fakeMessage.ContactFormService::MENTION_SENT_BY_EMAIL, $suivi->getDescription());
    }

    public function testDispatchContactFormForDeclarant()
    {
        $faker = Factory::create();
        $signalement = $this->signalementRepository->find(1);
        $signalement->setMailDeclarant($faker->email());
        $this->signalementManager->save($signalement);

        $fakeMessage = $faker->text();
        $this->contactFormService->dispatch(
            $faker->firstName(),
            $signalement->getMailDeclarant(),
            $fakeMessage,
        );

        $suivis = $signalement->getSuivis();
        $suivi = $suivis[\count($suivis) - 1];
        $this->assertEquals($fakeMessage.ContactFormService::MENTION_SENT_BY_EMAIL, $suivi->getDescription());
    }
}
