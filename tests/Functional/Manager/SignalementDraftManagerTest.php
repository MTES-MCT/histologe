<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\User;
use App\Factory\SignalementDraftFactory;
use App\Manager\SignalementDraftManager;
use App\Repository\SignalementDraftRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementDraftManagerTest extends WebTestCase
{
    public const TERRITORY_13 = 13;

    private EntityManagerInterface $entityManager;
    private ManagerRegistry $managerRegistry;
    private SignalementDraftFactory $signalementDraftFactory;
    private EventDispatcherInterface $eventDispatcher;
    private SignalementDraftManager $signalementDraftManager;
    private UrlGeneratorInterface $urlGenerator;
    private SignalementDraftRequestSerializer $signalementDraftRequestSerializer;
    private SignalementDraftRepository $signalementDraftRepository;

    protected function setUp(): void
    {
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->signalementDraftFactory = static::getContainer()->get(SignalementDraftFactory::class);
        $this->eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->signalementDraftRequestSerializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        $this->signalementDraftRepository = static::getContainer()->get(SignalementDraftRepository::class);

        $this->signalementDraftManager = new SignalementDraftManager(
            $this->signalementDraftFactory,
            $this->eventDispatcher,
            $this->managerRegistry,
            $this->urlGenerator,
            $this->signalementDraftRequestSerializer,
            $this->signalementDraftRepository,
        );

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
    }

    public function testFindSignalementDraftByAddressAndMail()
    {
        $signalementDraftRequest = new SignalementDraftRequest();
        $signalementDraftRequest->setAdresseLogementAdresse('33 Rue des phoceens 13002 Marseille');
        $signalementDraftRequest->setProfil(ProfileDeclarant::LOCATAIRE->name);
        $signalementDraftRequest->setVosCoordonneesOccupantEmail('locataire-01@histologe.fr');
        $signalementDraft = $this->signalementDraftManager->findSignalementDraftByAddressAndMail($signalementDraftRequest);

        $this->assertEquals('00000000-0000-0000-2023-locataire001', $signalementDraft->getUuid());
    }
}
