<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
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
    private const COLUMN_REFERENCE = 'ref Histologe';
    private const COLUMN_CREATED_AT = 'Année signalement';
    private const COLUMN_CLOSED_AT = 'année fermeture';
    private const COLUMN_STATUS = 'Statut';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository,
        private ParameterBagInterface $parameterBag,
        private UploadHandlerService $uploadHandlerService,
        private CsvParser $csvParser,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territory = $this->territoryRepository->findOneBy(['zip' => '73']);
        $from = 'csv/savoie_dossiers.csv';
        $to = $this->parameterBag->get('uploads_tmp_dir').'savoie_dossiers.csv';
        $this->uploadHandlerService->createTmpFileFromBucket($from, $to);
        $rows = $this->csvParser->parseAsDict($to);

        $progressBar = (new ProgressBar($output, \count($rows)));
        $progressBar->start();
        $countSignalement = 0;
        foreach ($rows as $row) {
            $status = null;
            $signalement = $this->signalementRepository->findOneBy([
                'reference' => $row[self::COLUMN_REFERENCE], 'territory' => $territory,
            ]);

            if ($signalement instanceof Signalement) {
                $currentCreatedAt = $signalement->getCreatedAt();
                if (!empty($row[self::COLUMN_CREATED_AT])) {
                    $newCreatedAt = $currentCreatedAt->setDate(
                        (int) $row[self::COLUMN_CREATED_AT],
                        (int) $currentCreatedAt->format('m'),
                        (int) $currentCreatedAt->format('d')
                    );
                    $newReferenceYear = $newCreatedAt->format('Y');
                    $signalement->setCreatedAt($newCreatedAt);
                    [$currentReferenceYear, $currentReferenceIndex] = explode('-', $signalement->getReference());
                    $signalement->setReference(sprintf('%s-%s', $newReferenceYear, $currentReferenceIndex));
                }

                if (!empty($row[self::COLUMN_STATUS])) {
                    if ('ouvert' === $row[self::COLUMN_STATUS]) {
                        $status = Signalement::STATUS_ACTIVE;
                    } elseif ('fermé' === $row[self::COLUMN_STATUS]) {
                        $status = Signalement::STATUS_CLOSED;
                        $currentClosedAt = $signalement->getClosedAt();
                        if (null !== $currentClosedAt) {
                            $newClosedAt = $currentClosedAt->setDate(
                                (int) $row[self::COLUMN_CLOSED_AT],
                                (int) $currentClosedAt->format('m'),
                                (int) $currentClosedAt->format('d')
                            );
                        } else {
                            $newClosedAt = (new \DateTimeImmutable())->setDate((int) $row[self::COLUMN_CLOSED_AT], 1, 1);
                        }

                        $signalement->setClosedAt($newClosedAt);
                    }
                    $signalement->setStatut($status);
                }

                $signalement->setIsImported(true);

                $this->entityManager->persist($signalement);
                ++$countSignalement;
                $progressBar->advance(1);
            }
        }

        $progressBar->finish();
        $io->success(sprintf('%d signalements updated', $countSignalement));

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
