<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Entity\Tag;
use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Manager\TagManager;
use App\Manager\TerritoryManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:fix-signalement-tag',
    description: 'Fix tag signalement tag',
)]
class FixSignalementTagCommand extends Command
{
    public const LEGACY_TERRITORY = [
        '81', '08', '29', '69', '71', '63', '47', '19', '2A', '31', '59', '64', '04', '06', '13',
    ];

    private Connection|null $connection;
    private Territory $territory;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private TerritoryManager $territoryManager,
        private SignalementManager $signalementManager,
        private TagManager $tagManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'The territory of signalement');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');
        if (!\in_array($territoryZip, self::LEGACY_TERRITORY)) {
            $io->error(sprintf('%s is not legacy territory', $territoryZip));

            return Command::FAILURE;
        }

        $io->info(sprintf('You passed an argument: %s', $territoryZip));
        $this->territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $territoryZip]);

        /* @var Connection $connection */
        $this->connection = $this->managerRegistry->getConnection('legacy_'.$territoryZip);
        $this->connection->connect();

        $this->cleanTagSignalement($input, $output);
        $legacySignalementTagList = $this->getLegacyTagSignalement($input, $output);
        $nbSignalementTagAdded = $this->addTagSignalement($legacySignalementTagList);

        $io->success($nbSignalementTagAdded.' has/have been added');

        return Command::SUCCESS;
    }

    private function cleanTagSignalement(InputInterface $input, OutputInterface $output): void
    {
        $signalements = $this->signalementManager->findBy(['territory' => $this->territory]);
        $nbTagRemoved = 0;
        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $tags = $signalement->getTags();

            foreach ($tags as $tag) {
                $tagToCheck = $this->tagManager->findOneBy([
                    'label' => $tag->getLabel(),
                    'territory' => $this->territory,
                ]);
                if (null === $tagToCheck) {
                    $signalement->getTags()->removeElement($tag);
                    $this->signalementManager->remove($signalement);
                    ++$nbTagRemoved;
                }
            }
        }
        $output->writeln(sprintf('%s tag signalement removed', $nbTagRemoved));
    }

    private function getLegacyTagSignalement(InputInterface $input, OutputInterface $output): array
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT s.uuid, t.label FROM signalement s INNER JOIN tag_signalement ts ON ts.signalement_id = s.id INNER JOIN tag t ON t.id = ts.tag_id ORDER BY s.uuid');

        return $statement->executeQuery()->fetchAllAssociative();
    }

    private function addTagSignalement(array $legacySignalementTagList): int
    {
        $i = 0;
        foreach ($legacySignalementTagList as $legacySignalementTag) {
            /** @var Tag $tag */
            $tag = $this->entityManager->getRepository(Tag::class)->findOneBy([
                'label' => $legacySignalementTag['label'],
                'territory' => $this->territory,
            ]);

            /** @var Signalement $signalement */
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                'uuid' => $legacySignalementTag['uuid'],
                'territory' => $this->territory,
            ]);

            if (null !== $tag && !$signalement->getTags()->contains($tag)) {
                $signalement->addTag($tag);
                $this->entityManager->persist($tag);
                ++$i;
            }
        }
        $this->entityManager->flush();

        return $i;
    }
}
