<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\EventListener\ActivityListener;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\TagManager;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\Parser\CsvParser;
use App\Service\Signalement\Import\SignalementImportMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:import-signalement',
    description: 'Import signalement on storage S3',
)]
class ImportSignalementCommand extends Command
{
    private const SITUATIONS = [
        'Sécurité des occupants',
        'Etat et propreté du logement',
        'confort du logement',
        'Etat du bâtiment',
        'Les espaces de vie',
        'Vie commune et voisinage',
    ];

    private ?Territory $territory = null;

    public function __construct(
        private ActivityListener $activityListener,
        private CsvParser $csvParser,
        private SignalementImportMapper $signalementImportMapper,
        private SignalementManager $signalementManager,
        private AffectationManager $affectationManager,
        private TagManager $tagManager,
        private TerritoryRepository $territoryRepository,
        private PartnerRepository $partnerRepository,
        private CritereRepository $critereRepository,
        private ParameterBagInterface $parameterBag,
        private EventDispatcherInterface $eventDispatcher,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'Territory zip to target');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->entityManager->getEventManager()->removeEventSubscriber($this->activityListener);
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'signalement_'.$territoryZip.'.csv';
        $this->territory = $this->territoryRepository->findOneBy(['zip' => $territoryZip]);

        if (null === $this->territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $headers = $this->csvParser->getHeaders($toFile);
        $data = $this->csvParser->parseAsDict($toFile);
        $countSignalement = 0;
        foreach ($data as $item) {
            $dataMapped = $this->signalementImportMapper->map($headers, $item);
            if (!empty($dataMapped)) {
                ++$countSignalement;
                $signalement = $this->signalementManager->createOrGet($this->territory, $dataMapped, true);
                $signalement = $this->loadTags($signalement, $dataMapped);
                $this->loadAffectation($signalement, $dataMapped);

                foreach (self::SITUATIONS as $situation) {
                    $signalement = $this->loadSignalementSituation($signalement, $dataMapped, $situation);
                }

                $this->signalementManager->persist($signalement);
                $io->writeln(sprintf('%s added', $signalement->getReference()));
            }
        }
        $this->signalementManager->flush();
        $io->success(sprintf('%s have been imported', $countSignalement));
        $this->entityManager->getEventManager()->addEventSubscriber($this->activityListener);

        return Command::SUCCESS;
    }

    private function loadTags(Signalement $signalement, array $dataMapped): Signalement
    {
        if (isset($dataMapped['tags']) && !empty($dataMapped['tags'])) {
            $tag = $this->tagManager->createOrGet($this->territory, $dataMapped['tags']);
            $signalement->addTag($tag);
        }

        return $signalement;
    }

    private function loadAffectation(Signalement $signalement, array $dataMapped): void
    {
        if (isset($dataMapped['partners']) && !empty($dataMapped['partners'])) {
            $partnersName = explode(',', $dataMapped['partners']);
            foreach ($partnersName as $partnerName) {
                $partner = $this->partnerRepository->findOneBy([
                    'nom' => $partnerName,
                    'territory' => $this->territory,
                ]);

                if (null !== $partner) {
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $partner?->getUsers()?->first()
                    );

                    $affectation
                        ->setStatut(Affectation::STATUS_CLOSED)
                        ->setAnsweredAt(new \DateTimeImmutable())
                        ->setMotifCloture($dataMapped['motifCloture']);

                    $this->affectationManager->persist($affectation);
                }
            }
        }
    }

    private function loadSignalementSituation(
        Signalement $signalement,
        array $dataMapped,
        string $situation
    ): Signalement {
        if (isset($dataMapped[$situation]) && !empty($dataMapped[$situation])) {
            foreach ($dataMapped[$situation] as $critereLabel => $etat) {
                /** @var Critere $critere */
                $critere = $this->critereRepository->findByLabel(trim($critereLabel));
                /** @var Criticite $criticite */
                $criticite = $critere->getCriticites()->filter(function (Criticite $criticite) use ($etat) {
                    return $criticite->getScore() === Criticite::ETAT_LABEL[trim($etat)];
                })->first();

                $signalement
                    ->addCriticite($criticite)
                    ->addSituation($critere->getSituation())
                    ->addCritere($critere);
            }
        }

        return $signalement;
    }
}
