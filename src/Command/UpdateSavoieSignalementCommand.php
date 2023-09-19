<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:update-savoie-dossier',
    description: 'Update Savoie signalement',
)]
class UpdateSavoieSignalementCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository,
        private CsvParser $csvParser,
        private ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territory = $this->territoryRepository->findOneBy(['zip' => '73']);
        $rows = $this->csvParser->parseAsDict($this->parameterBag->get('uploads_tmp_dir').'savoie_dossiers.csv');

        $progressBar = (new ProgressBar($output, \count($rows)));
        $progressBar->start();
        $countSignalement = 0;
        foreach ($rows as $row) {
            $status = null;
            $signalement = $this->signalementRepository->findOneBy(['reference' => $row['ref Histologe'], 'territory' => $territory]);

            if ($signalement instanceof Signalement) {
                $currentCreatedAt = $signalement->getCreatedAt();
                if (!empty($row['Année signalement'])) {
                    $newCreatedAt = $currentCreatedAt->setDate(
                        (int) $row['Année signalement'],
                        (int) $currentCreatedAt->format('m'),
                        (int) $currentCreatedAt->format('d')
                    );
                    $newReferenceYear = $newCreatedAt->format('Y');
                    $signalement->setCreatedAt($newCreatedAt);
                    [$currentReferenceYear, $currentReferenceIndex] = explode('-', $signalement->getReference());
                    $signalement->setReference(sprintf('%s-%s', $newReferenceYear, $currentReferenceIndex));
                }

                if (!empty($row['Statut'])) {
                    if ('ouvert' === $row['Statut']) {
                        $status = Signalement::STATUS_ACTIVE;
                    } elseif ('fermé' === $row['Statut']) {
                        $status = Signalement::STATUS_CLOSED;
                        $currentClosedAt = $signalement->getClosedAt();
                        if (null !== $currentClosedAt) {
                            $newClosedAt = $currentClosedAt->setDate(
                                (int) $row['année fermeture'],
                                (int) $currentClosedAt->format('m'),
                                (int) $currentClosedAt->format('d')
                            );
                        } else {
                            $newClosedAt = (new \DateTimeImmutable())->setDate((int) $row['année fermeture'], 1, 1);
                        }

                        $signalement->setClosedAt($newClosedAt);
                    }
                }

                if (null !== $status) {
                    $signalement->setStatut($status);
                }

                $signalement->setIsImported(true);

                $this->entityManager->persist($signalement);
                ++$countSignalement;
                $progressBar->advance(1);
            }
        }

        $progressBar->finish();
        $io->success(sprintf('%d signalements updates', $countSignalement));

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
