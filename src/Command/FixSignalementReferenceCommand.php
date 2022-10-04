<?php

namespace App\Command;

use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Manager\TerritoryManager;
use App\Repository\SignalementRepository;
use App\Service\Signalement\ReferenceGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-signalement-reference',
    description: 'Fix signalement with duplicated reference',
)]
class FixSignalementReferenceCommand extends Command
{
    public function __construct(
        private SignalementManager $signalementManager,
        private TerritoryManager $territoryManager,
        private ReferenceGenerator $referenceGenerator,
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

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);
        if (null === $territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $duplicatedReferences = $signalementRepository->findDuplicatedReferences($territory);

        foreach ($duplicatedReferences as $duplicatedReference) {
            $signalement = $signalementRepository->findOneBy(
                ['reference' => $duplicatedReference['reference'], 'territory' => $territory],
                ['createdAt' => 'DESC']
            );
            $reference = $this->referenceGenerator->generate($territory);
            $signalement->setReference($reference);
            $this->signalementManager->save($signalement);
            $io->success(sprintf('Signalement %s has been updated with a correct reference %s',
                $signalement->getUuid(),
                $signalement->getReference())
            );
        }

        return Command::SUCCESS;
    }
}
