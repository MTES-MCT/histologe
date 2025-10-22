<?php

namespace App\Tests\Unit\Messenger\MessageHandler;

use App\Entity\DesordreCritere;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Messenger\Message\NewSignalementCheckFileMessage;
use App\Messenger\MessageHandler\NewSignalementCheckFileMessageHandler;
use App\Repository\DesordreCritereRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NewSignalementCheckFileMessageHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
    }

    public function testMissingDocumentsString(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);

        $handler = $this->getHandler();

        $this->assertStringContainsString('le bail du logement', $handler->getMissingDocumentsString($signalement));
    }

    public function testMissingDesordresPhotosString(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);

        $handler = $this->getHandler();

        $this->assertStringContainsString('Propreté et entretien', $handler->getMissingDesordresPhotosString($signalement));
    }

    public function testProcessNewSignalementCheckFileNotSent(): void
    {
        $handler = $this->checkSignalement('00000000-0000-0000-2024-000000000004');

        $suivi = $handler->suivi;
        $this->assertEmpty($suivi);
    }

    public function testProcessNewSignalementCheckFileSent(): void
    {
        $handler = $this->checkSignalement('00000000-0000-0000-2023-000000000027');

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringContainsString('diagnostic', $handler->description);
    }

    public function testProcessSignalementRefusedNotSent(): void
    {
        // on n'envoie pas de demande de documents si le signalement est refusé
        $handler = $this->checkSignalement('00000000-0000-0000-2023-000000000021');

        $suivi = $handler->suivi;
        $this->assertEmpty($suivi);
    }

    public function testProcessSignalementAcceptedAndAlreadySuiviPartner(): void
    {
        // on n'envoie pas de demande de documents si le signalement est accepté et qu'il y a un suivi partenaire
        $handler = $this->checkSignalement('00000000-0000-0000-2022-000000000008');

        $suivi = $handler->suivi;
        $this->assertEmpty($suivi);
    }

    public function testProcessSignalementAcceptedButNoSuiviPartner(): void
    {
        $handler = $this->checkSignalement('00000000-0000-0000-2023-000000000027', SignalementStatus::ACTIVE);

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringContainsString('diagnostic', $handler->description);
    }

    private function getHandler(): NewSignalementCheckFileMessageHandler
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var DesordreCritereRepository $desordreCritereRepository */
        $desordreCritereRepository = $this->entityManager->getRepository(DesordreCritere::class);
        /** @var LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        /** @var SuiviManager $suiviManager */
        $suiviManager = $this->createMock(SuiviManager::class);
        /** @var Security $security */
        $security = $this->createMock(Security::class);
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $handler = new NewSignalementCheckFileMessageHandler(
            $signalementRepository,
            $userRepository,
            $desordreCritereRepository,
            $loggerMock,
            $suiviManager,
            $parameterBag,
            $security,
        );

        return $handler;
    }

    private function checkSignalement(string $uuid, ?SignalementStatus $signalementStatus = null): NewSignalementCheckFileMessageHandler
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => $uuid]);
        if (null !== $signalementStatus) {
            $signalement->setStatut($signalementStatus);
        }
        $newSignalementCheckFileMessage = new NewSignalementCheckFileMessage($signalement->getId());

        $handler = $this->getHandler();
        $handler->__invoke($newSignalementCheckFileMessage);

        return $handler;
    }

    public function testProcessSignalementWithoutBailleurCoordonnees(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);
        $signalement->setMailProprio(null);
        $signalement->setTelProprio(null);
        // $this->entityManager->flush();

        $newSignalementCheckFileMessage = new NewSignalementCheckFileMessage($signalement->getId());

        $handler = $this->getHandler();
        $handler->__invoke($newSignalementCheckFileMessage);

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringContainsString(
            'coordonnées du propriétaire',
            $handler->description,
            'Le message doit demander les coordonnées du bailleur.'
        );
    }

    public function testProcessSignalementWithBailleurCoordonnees(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);
        $signalement->setMailProprio('bailleur@example.com');
        $signalement->setTelProprio(null);

        $newSignalementCheckFileMessage = new NewSignalementCheckFileMessage($signalement->getId());

        $handler = $this->getHandler();
        $handler->__invoke($newSignalementCheckFileMessage);

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringNotContainsString(
            'coordonnées de votre propriétaire',
            $handler->description,
            'Le message ne doit pas demander les coordonnées du bailleur si elles sont déjà renseignées.'
        );
    }

    public function testProcessSignalementWithoutBailleurCoordonneesButProfilBailleur(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);
        $signalement->setMailProprio(null);
        $signalement->setTelProprio(null);
        $signalement->setProfileDeclarant(ProfileDeclarant::BAILLEUR);

        $newSignalementCheckFileMessage = new NewSignalementCheckFileMessage($signalement->getId());

        $handler = $this->getHandler();
        $handler->__invoke($newSignalementCheckFileMessage);

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringNotContainsString(
            'coordonnées de votre propriétaire',
            $handler->description,
            'Le message ne doit pas demander les coordonnées du bailleur si c\'est un profil Bailleur.'
        );
    }
}
