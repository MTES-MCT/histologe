<?php

namespace App\Command;

use App\Repository\BailleurRepository;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-bailleur-link-on-signalement',
    description: 'Add Bailleur link on Signalement',
)]
class AddBailleurLinkOnSignalementCommand extends Command
{
    private $bailleursByNom = [];
    private $bailleursByRaison = [];
    private $nbLinkAdded = 0;

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly BailleurRepository $bailleurRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->bailleursByNom = $this->bailleurRepository->findAllIndexedByNameSanitizedWithBailleurTerritories();
        $this->bailleursByRaison = $this->bailleurRepository->findAllIndexedByNameSanitizedWithBailleurTerritories(true);

        $signalements = $this->signalementRepository->findAllWithoutBailleurPublicLink();
        foreach ($signalements as $signalement) {
            $nomProprioSanitized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtoupper($signalement->getNomProprio()));
            if (isset($this->bailleursByNom[$nomProprioSanitized])) {
                $bailleur = $this->bailleursByNom[$nomProprioSanitized];
                $signalement->setBailleur($bailleur);
                ++$this->nbLinkAdded;
            } elseif (isset($this->bailleursByRaison[$nomProprioSanitized])) {
                $bailleur = $this->bailleursByRaison[$nomProprioSanitized];
                $signalement->setBailleur($bailleur);
                ++$this->nbLinkAdded;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%s bailleur(s) have been linked to signalements.', $this->nbLinkAdded));

        return Command::SUCCESS;
    }
}
