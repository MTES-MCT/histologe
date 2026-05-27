<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Command\CommandContext;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Entity\UserSignalementSubscription;
use App\Factory\HistoryEntryFactory;
use App\Manager\HistoryEntryManager;
use App\Repository\AffectationRepository;
use App\Repository\HistoryEntryRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
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
            $this->entityManager,
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
        $this->assertEquals($affectation->getAffectedBy()->getNomComplet(true), $historyEntry->getUser()->getNomComplet(true));
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

    public function testGetAffectationHistoryDeleteAppearsInBothAdminAndPartnerSections(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($adminUser);

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-8']);
        $affectation = $signalement->getAffectations()->first();
        $this->assertInstanceOf(Affectation::class, $affectation);
        $partnerName = $affectation->getPartner()->getNom();

        $historyEntry = $this->historyEntryManager->create(
            HistoryEntryEvent::DELETE,
            $affectation,
            ['partner' => $affectation->getPartner()->getId()]
        );
        $this->entityManager->persist($historyEntry);
        $this->entityManager->flush();

        $historyEntries = $this->historyEntryManager->getAffectationHistory($signalement);

        $hasDeleteAction = static fn (array $entries) => !empty(array_filter(
            array_column($entries, 'Action'),
            static fn (string $action) => str_contains($action, "a supprimé l'affectation du partenaire")
        ));

        $this->assertArrayHasKey(Partner::DEFAULT_PARTNER, $historyEntries);
        $this->assertTrue($hasDeleteAction($historyEntries[Partner::DEFAULT_PARTNER]));

        $this->assertArrayHasKey($partnerName, $historyEntries);
        $this->assertTrue($hasDeleteAction($historyEntries[$partnerName]));
    }

    public function testGetAffectationHistorySubscriptionAppearsUnderOriginalPartnerAfterTransfer(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-8']);

        $subscription = $this->userSignalementSubscriptionRepository->findOneBy([
            'user' => $user,
            'signalement' => $signalement,
        ]);

        $userPartner = $user->getUserPartners()->first();
        $this->assertInstanceOf(UserPartner::class, $userPartner);
        $currentPartnerName = $userPartner->getPartner()->getNom();

        $oldPartner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);

        $subscriptionEntry = (new HistoryEntry())
            ->setEvent(HistoryEntryEvent::CREATE)
            ->setEntityId($subscription?->getId() ?? 0)
            ->setEntityName(str_replace(HistoryEntryFactory::ENTITY_PROXY_PREFIX, '', UserSignalementSubscription::class))
            ->setUser($user)
            ->setSignalement($signalement)
            ->setChanges([])
            ->setCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'));
        $this->entityManager->persist($subscriptionEntry);

        $userPartnerUpdateEntry = (new HistoryEntry())
            ->setEvent(HistoryEntryEvent::UPDATE)
            ->setEntityId($userPartner->getId())
            ->setEntityName(str_replace(HistoryEntryFactory::ENTITY_PROXY_PREFIX, '', UserPartner::class))
            ->setChanges(['partner' => ['old' => $oldPartner->getId(), 'new' => $userPartner->getPartner()->getId()]])
            ->setCreatedAt(new \DateTimeImmutable('2020-01-01 11:00:00'));
        $this->entityManager->persist($userPartnerUpdateEntry);

        $this->entityManager->flush();

        $historyEntries = $this->historyEntryManager->getAffectationHistory($signalement);

        $hasSubscriptionAction = static fn (array $entries) => !empty(array_filter(
            array_column($entries, 'Action'),
            static fn (string $action) => str_contains($action, 'a rejoint le dossier')
                || str_contains($action, 'a attribué le dossier')
        ));

        $this->assertArrayHasKey($oldPartner->getNom(), $historyEntries);
        $this->assertTrue($hasSubscriptionAction($historyEntries[$oldPartner->getNom()]));

        $this->assertFalse(
            isset($historyEntries[$currentPartnerName])
            && $hasSubscriptionAction($historyEntries[$currentPartnerName])
        );
    }

    public function testGetAffectationHistory(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(
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
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(
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
        $this->entityManager->persist($historyEntry);
        $this->entityManager->flush();

        $historyEntries = $this->historyEntryManager->getAffectationHistory($signalement);

        $this->assertIsArray($historyEntries);

        $this->assertNotEmpty($historyEntries[$affectations[0]->getPartner()->getNom()]);
        $this->assertEquals(2, \count($historyEntries[$affectations[0]->getPartner()->getNom()]));

        $entry = $historyEntries[$affectations[0]->getPartner()->getNom()][0];
        $this->assertArrayHasKey('Date', $entry);
        $this->assertArrayHasKey('Action', $entry);
        $this->assertStringContainsString('a réouvert l\'affectation pour le partenaire', (string) $entry['Action']);
        $this->assertArrayHasKey('Id', $entry);
        $this->assertEquals($affectations[0]->getId(), $entry['Id']);
    }
}
