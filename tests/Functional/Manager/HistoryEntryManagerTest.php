<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\User;
use App\Factory\HistoryEntryFactory;
use App\Manager\HistoryEntryManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HistoryEntryManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private HistoryEntryFactory $historyEntryFactory;
    private HistoryEntryManager $historyEntryManager;

    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->historyEntryFactory = static::getContainer()->get(HistoryEntryFactory::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->historyEntryManager = new HistoryEntryManager(
            $this->historyEntryFactory,
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
            entityId: $user->getId(),
            entityName: User::class,
            user: $user
        );

        $this->assertInstanceOf(HistoryEntry::class, $historyEntry);
        $this->assertEquals($historyEntry->getEvent(), HistoryEntryEvent::LOGIN);
    }
}
