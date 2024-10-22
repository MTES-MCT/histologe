<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Command\CommandContext;
use App\Entity\Affectation;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\HistoryEntryFactory;
use App\Manager\HistoryEntryManager;
use App\Repository\AffectationRepository;
use App\Repository\HistoryEntryRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class HistoryEntryManagerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private CommandContext $commandContext;
    private HistoryEntryFactory $historyEntryFactory;
    private HistoryEntryManager $historyEntryManager;
    private HistoryEntryRepository $historyEntryRepository;
    private AffectationRepository $affectationRepository;
    private PartnerRepository $partnerRepository;

    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        // $kernel = self::bootKernel();
        $this->client = static::createClient();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->historyEntryFactory = static::getContainer()->get(HistoryEntryFactory::class);
        $this->historyEntryRepository = static::getContainer()->get(HistoryEntryRepository::class);
        $this->affectationRepository = static::getContainer()->get(AffectationRepository::class);
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->requestStack = static::getContainer()->get(RequestStack::class);
        $this->commandContext = static::getContainer()->get(CommandContext::class);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->historyEntryManager = new HistoryEntryManager(
            $this->historyEntryFactory,
            $this->historyEntryRepository,
            $this->affectationRepository,
            $this->partnerRepository,
            $this->requestStack,
            $this->commandContext,
            $this->managerRegistry,
            HistoryEntry::class,
        );
    }

    public function testCreateHistoryEntry()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::LOGIN,
            entityHistory: $user,
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::LOGIN, $historyEntry->getEvent());
    }

    public function testCreateAffectationHistoryEntry()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);
        $this->client->loginUser($user);

        $affectation = $this->affectationRepository->findOneBy(['statut' => Affectation::STATUS_WAIT]);

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::CREATE,
            entityHistory: $affectation
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::CREATE, $historyEntry->getEvent());
        $this->assertEquals($affectation->getId(), $historyEntry->getEntityId());
        $this->assertEquals($user->getFullName(), $historyEntry->getUser()->getFullName());
    }

    public function testUpdateAffectationHistoryEntry()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);
        $this->client->loginUser($user);

        $affectation = $this->affectationRepository->findOneBy(['statut' => Affectation::STATUS_WAIT]);

        $affectation->setStatut(Affectation::STATUS_ACCEPTED);
        $changes = [];
        $changes['statut'] = [
            'old' => Affectation::STATUS_WAIT,
            'new' => Affectation::STATUS_ACCEPTED,
        ];

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::UPDATE,
            entityHistory: $affectation,
            changes: $changes
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::UPDATE, $historyEntry->getEvent());
        $this->assertEquals(Affectation::STATUS_ACCEPTED, $historyEntry->getChanges()['statut']['new']);
    }

    public function testDeleteAffectationHistoryEntry()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);
        $this->client->loginUser($user);

        $affectation = $this->affectationRepository->findOneBy(['statut' => Affectation::STATUS_WAIT]);

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::DELETE,
            entityHistory: $affectation,
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::DELETE, $historyEntry->getEvent());
        $this->assertEquals($affectation->getId(), $historyEntry->getEntityId());
    }

    public function testGetAffectationHistory()
    {
        $featureHistoriqueAffectations = static::getContainer()->getParameter('feature_historique_affectations');
        if (!$featureHistoriqueAffectations) {
            $this->markTestSkipped('La fonctionnalité "feature_historique_affectations" est désactivée.');
        }

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => '2022-8']
        );
        $signalementId = $signalement->getId();
        $affectations = $signalement->getAffectations();

        $historyEntries = $this->historyEntryManager->getAffectationHistory($signalementId);

        $this->assertIsArray($historyEntries);
        $this->assertArrayHasKey('N/A', $historyEntries); // on est sur les fixtures, on ne sait donc pas qui a créé l'entrée

        $this->assertNotEmpty($historyEntries['N/A']);
        $this->assertEquals(3, \count($historyEntries['N/A']));

        $entry = $historyEntries['N/A'][0];
        $this->assertArrayHasKey('Date', $entry);
        $this->assertArrayHasKey('Action', $entry);
        $this->assertEquals('Système a affecté le signalement au partenaire Partenaire 13-02', $entry['Action']);
        $this->assertArrayHasKey('Id', $entry);
        $this->assertEquals($affectations[0]->getId(), $entry['Id']);
    }
}
