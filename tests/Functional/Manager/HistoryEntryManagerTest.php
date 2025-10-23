<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Command\CommandContext;
use App\Entity\Enum\AffectationStatus;
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
use App\Repository\UserSignalementSubscriptionRepository;
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
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private UserRepository $userRepository;
    private PartnerRepository $partnerRepository;

    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->historyEntryFactory = static::getContainer()->get(HistoryEntryFactory::class);
        $this->historyEntryRepository = static::getContainer()->get(HistoryEntryRepository::class);
        $this->affectationRepository = static::getContainer()->get(AffectationRepository::class);
        $this->userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->requestStack = static::getContainer()->get(RequestStack::class);
        $this->commandContext = static::getContainer()->get(CommandContext::class);
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $em;
        $this->historyEntryManager = new HistoryEntryManager(
            $this->historyEntryFactory,
            $this->historyEntryRepository,
            $this->affectationRepository,
            $this->userSignalementSubscriptionRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->requestStack,
            $this->commandContext,
            $this->managerRegistry,
            HistoryEntry::class,
        );
    }

    public function testCreateHistoryEntry(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@signal-logement.fr']);

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::LOGIN,
            entityHistory: $user,
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::LOGIN, $historyEntry->getEvent());
    }

    public function testCreateAffectationHistoryEntry(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $affectation = $this->affectationRepository->findOneBy(['statut' => AffectationStatus::WAIT]);

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::CREATE,
            entityHistory: $affectation
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::CREATE, $historyEntry->getEvent());
        $this->assertEquals($affectation->getId(), $historyEntry->getEntityId());
        $this->assertEquals($affectation->getAffectedBy()->getFullname(), $historyEntry->getUser()->getFullName());
    }

    public function testUpdateAffectationHistoryEntry(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $affectation = $this->affectationRepository->findOneBy(['statut' => AffectationStatus::WAIT]);

        $affectation->setStatut(AffectationStatus::ACCEPTED);
        $changes = [];
        $changes['statut'] = [
            'old' => AffectationStatus::WAIT->value,
            'new' => AffectationStatus::ACCEPTED->value,
        ];

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::UPDATE,
            entityHistory: $affectation,
            changes: $changes
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::UPDATE, $historyEntry->getEvent());
        $this->assertEquals(AffectationStatus::ACCEPTED->value, $historyEntry->getChanges()['statut']['new']);
    }

    public function testDeleteAffectationHistoryEntry(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $affectation = $this->affectationRepository->findOneBy(['statut' => AffectationStatus::WAIT]);

        $historyEntry = $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::DELETE,
            entityHistory: $affectation,
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals(HistoryEntryEvent::DELETE, $historyEntry->getEvent());
        $this->assertEquals($affectation->getId(), $historyEntry->getEntityId());
    }

    public function testGetAffectationHistory(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => '2022-8']
        );

        $historyEntries = $this->historyEntryManager->getAffectationHistory($signalement);

        $this->assertIsArray($historyEntries);
        $this->assertNotEmpty($historyEntries);
    }

    public function testGetAffectationHistoryWithAffectation(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@signal-logement.fr']);
        $this->client->loginUser($user);

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => '2022-8']
        );
        $affectations = $signalement->getAffectations();
        $changes = [];
        $changes['statut'] = [];
        $changes['statut']['new'] = AffectationStatus::ACCEPTED->value;
        $changes['statut']['old'] = $affectations[0]->getStatut()->value;
        $historyEntry = $this->historyEntryManager->create(HistoryEntryEvent::UPDATE, $affectations[0], $changes);
        $source = $this->historyEntryManager->getSource();
        $historyEntry->setSource($source);
        $this->historyEntryManager->save($historyEntry);
        $this->entityManager->persist($historyEntry);

        $historyEntries = $this->historyEntryManager->getAffectationHistory($signalement);

        $this->assertIsArray($historyEntries);

        $this->assertNotEmpty($historyEntries[$affectations[0]->getPartner()->getNom()]);
        $this->assertEquals(2, \count($historyEntries[$affectations[0]->getPartner()->getNom()]));

        $entry = $historyEntries[$affectations[0]->getPartner()->getNom()][0];
        $this->assertArrayHasKey('Date', $entry);
        $this->assertArrayHasKey('Action', $entry);
        $this->assertStringContainsString('a réouvert l\'affectation pour le partenaire', $entry['Action']);
        $this->assertArrayHasKey('Id', $entry);
        $this->assertEquals($affectations[0]->getId(), $entry['Id']);
    }
}
