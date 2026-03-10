<?php

namespace App\Service\Import\Signalement;

use App\Entity\Affectation;
use App\Entity\Criticite;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\FileManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\TagManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use App\Repository\PartnerRepository;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SignalementImportLoader
{
    private const FLUSH_COUNT = 20;
    private const REGEX_DATE_FORMAT_CSV = '/\d{4}\/\d{2}\/\d{2}/';

    private const SITUATIONS = [
        'Sécurité des occupants',
        'Etat et propreté du logement',
        'confort du logement',
        'Etat du bâtiment',
        'Les espaces de vie',
        'Vie commune et voisinage',
    ];

    /**
     * @var array{
     *   count_signalement: int,
     *   partners_not_found: array<string, int>,
     *   motif_cloture_not_found: array<string, int>,
     *   files_not_found: array<string, string>,
     *   desordres_not_found: array<string, int>,
     * }
     */
    private array $metadata = [
        'count_signalement' => 0,
        'partners_not_found' => [],
        'motif_cloture_not_found' => [],
        'files_not_found' => [],
        'desordres_not_found' => [],
    ];

    private ?User $userSystem = null;
    private array $indexedCriteres = [];
    private array $indexedCriticites = [];
    private array $indexedPartners = [];

    public function __construct(
        private SignalementImportMapper $signalementImportMapper,
        private SignalementManager $signalementManager,
        private TagManager $tagManager,
        private AffectationManager $affectationManager,
        private SuiviManager $suiviManager,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
        private CriticiteCalculator $criticiteCalculator,
        private SignalementQualificationUpdater $signalementQualificationUpdater,
        private FileManager $fileManager,
        private FilesystemOperator $fileStorage,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private HtmlSanitizerInterface $htmlSanitizer,
        private UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        private readonly CritereRepository $critereRepository,
        private readonly CriticiteRepository $criticiteRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @param array<int, string>               $headers
     *
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function load(Territory $territory, array $data, array $headers, ?OutputInterface $output = null): void
    {
        $countSignalement = 0;
        $this->userSystem = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
        if ($output) {
            $output->writeln('Vérification des données à importer.');
        }

        $dataMappedOk = [];
        foreach ($data as $item) {
            $dataMappedToCheck = $this->signalementImportMapper->map($headers, $item);
            if (empty($dataMappedToCheck)) {
                continue;
            }
            foreach (self::SITUATIONS as $situation) {
                $this->loadSignalementSituation($dataMappedToCheck, $situation);
            }
            $this->loadAffectation($territory, $dataMappedToCheck);
            $this->loadFiles($dataMappedToCheck['photos']);
            $this->loadFiles($dataMappedToCheck['documents']);
            if ($dataMappedToCheck['motifCloture'] && !MotifCloture::tryFrom($dataMappedToCheck['motifCloture'])) {
                if (!isset($this->metadata['motif_cloture_not_found'][$dataMappedToCheck['motifCloture']])) {
                    $this->metadata['motif_cloture_not_found'][$dataMappedToCheck['motifCloture']] = 0;
                }
                ++$this->metadata['motif_cloture_not_found'][$dataMappedToCheck['motifCloture']];
            }
            $dataMappedOk[] = $dataMappedToCheck;
        }

        if ($this->hasErrors()) {
            $this->logger->info('Des erreurs ont été détectées, l\'import est annulé.');

            return;
        }

        if ($output) {
            $output->writeln('Aucune erreur détectée, lancement de l\'import.');
            $progressBar = new ProgressBar($output);
            $progressBar->start(\count($dataMappedOk));
        }

        foreach ($dataMappedOk as $dataMapped) {
            ++$countSignalement;
            $signalement = $this->signalementManager->createOrUpdateFromArrayForImport($territory, $dataMapped);
            if (!$signalement->getProfileDeclarant()) {
                if (!$signalement->getIsNotOccupant()) {
                    $signalement->setProfileDeclarant(ProfileDeclarant::LOCATAIRE);
                } elseif (OccupantLink::PRO->name == mb_strtoupper($signalement->getLienDeclarantOccupant())) {
                    $signalement->setProfileDeclarant(ProfileDeclarant::TIERS_PRO);
                } else {
                    $signalement->setProfileDeclarant(ProfileDeclarant::TIERS_PARTICULIER);
                }
            }
            $this->signalementManager->save($signalement);

            $this->loadTags($signalement, $territory, $dataMapped);
            foreach (self::SITUATIONS as $situation) {
                $this->loadSignalementSituation($dataMapped, $situation, $signalement);
            }

            $signalement->setScore($this->criticiteCalculator->calculate($signalement));
            $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);

            $affectationCollection = $this->loadAffectation($territory, $dataMapped, $signalement);
            foreach ($affectationCollection as $affectation) {
                $signalement->addAffectation($affectation);
            }

            $suiviCollection = $this->loadSuivi($signalement, $dataMapped);
            foreach ($suiviCollection as $suivi) {
                $signalement->addSuivi($suivi);
            }

            $this->loadFiles($dataMapped['photos'], signalement: $signalement);
            $this->loadFiles($dataMapped['documents'], signalement: $signalement);

            $this->metadata['count_signalement'] = $countSignalement;
            if (0 === $countSignalement % self::FLUSH_COUNT) {
                $this->logger->info(\sprintf('in progress - %s signalements saved', $countSignalement));
                $this->signalementManager->flush();
            }
            if ($output) {
                $progressBar->advance();
            }
        }

        $this->signalementManager->flush();
        if ($output) {
            $progressBar->finish();
        }
    }

    /**
     * @return array{
     *   count_signalement: int,
     *   partners_not_found: array<string, int>,
     *   motif_cloture_not_found: array<string, int>,
     *   files_not_found: array<string, string>,
     *   desordres_not_found: array<string, int>,
     * }
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function hasErrors(): bool
    {
        return \count($this->metadata['partners_not_found']) > 0
            || \count($this->metadata['motif_cloture_not_found']) > 0
            || \count($this->metadata['files_not_found']) > 0
            || \count($this->metadata['desordres_not_found']) > 0;
    }

    /**
     * @param array<string, mixed> $dataMapped
     */
    private function loadTags(Signalement $signalement, Territory $territory, array $dataMapped): void
    {
        if (isset($dataMapped['tags']) && !empty($dataMapped['tags'])) {
            $tag = $this->tagManager->createOrGet($territory, $dataMapped['tags']);
            $signalement->addTag($tag);
        }
    }

    /**
     * @param array<string, mixed> $dataMapped
     *
     * @return ArrayCollection<int, Affectation>
     */
    private function loadAffectation(Territory $territory, array $dataMapped, ?Signalement $signalement = null): ArrayCollection
    {
        $affectationCollection = new ArrayCollection();
        if (isset($dataMapped['partners']) && !empty($dataMapped['partners'])) {
            if (str_contains($dataMapped['partners'], ',') && '62' !== $territory->getZip()) {
                $partnersName = explode(',', $dataMapped['partners']);
            } elseif (str_contains($dataMapped['partners'], '|')) {
                $partnersName = explode('|', $dataMapped['partners']);
            } else {
                $partnersName = [$dataMapped['partners']];
            }
            foreach ($partnersName as $partnerName) {
                $partnerNameCleaned = mb_trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $partnerName)); // remove non printable chars
                if (!array_key_exists($partnerNameCleaned, $this->indexedPartners)) {
                    $this->indexedPartners[$partnerNameCleaned] = $this->partnerRepository->findOneBy(['nom' => $partnerNameCleaned, 'territory' => $territory]);
                }
                $partner = $this->indexedPartners[$partnerNameCleaned];
                if (!$partner) {
                    if (!isset($this->metadata['partners_not_found'][$partnerNameCleaned])) {
                        $this->metadata['partners_not_found'][$partnerNameCleaned] = 0;
                    }
                    ++$this->metadata['partners_not_found'][$partnerNameCleaned];
                    continue;
                }
                if (!$signalement) {
                    continue;
                }

                $affectation = $this->affectationManager->createAffectationFrom(
                    signalement: $signalement,
                    partner: $partner,
                    user: $partner->getUsers()->isEmpty() ? null : $partner->getUsers()->first(),
                    dispatchEvent: false
                );
                if ($affectation instanceof Affectation) {
                    $affectation
                        ->setCreatedAt($dataMapped['createdAt'])
                        ->setAnsweredAt($dataMapped['createdAt']);
                    if (MotifCloture::tryFrom($dataMapped['motifCloture'])) {
                        $affectation = $this->affectationManager->closeAffectation(
                            affectation: $affectation,
                            user: $this->userSystem,
                            partner: null,
                            motif: MotifCloture::tryFrom($dataMapped['motifCloture'])
                        );
                    } else {
                        $affectation->setStatut(AffectationStatus::ACCEPTED);
                        $this->userSignalementSubscriptionManager->createDefaultSubscriptionsForAffectation($affectation);
                    }
                    $affectationCollection->add($affectation);
                }
            }
        }

        return $affectationCollection;
    }

    /**
     * @param array<string, mixed> $dataMapped
     */
    private function loadSignalementSituation(
        array $dataMapped,
        string $situation,
        ?Signalement $signalement = null,
    ): void {
        if (isset($dataMapped[$situation]) && !empty($dataMapped[$situation])) {
            foreach ($dataMapped[$situation] as $critereLabel => $etat) {
                $critereLabel = trim($critereLabel);
                if (!array_key_exists($critereLabel, $this->indexedCriteres)) {
                    /* @var Critere $critere */
                    $this->indexedCriteres[$critereLabel] = $this->critereRepository->findByLabel($critereLabel);
                }
                try {
                    if (null !== $this->indexedCriteres[$critereLabel]) {
                        /** @var Criticite $criticite */
                        $criticite = $this->indexedCriteres[$critereLabel]->getCriticites()->filter(function (Criticite $c) use ($etat) {
                            return $c->getScore() === Criticite::ETAT_LABEL[trim($etat)];
                        })->first();
                    } else {
                        if (!array_key_exists($critereLabel, $this->indexedCriticites)) {
                            $this->indexedCriticites[$critereLabel] = $this->criticiteRepository->findByLabel($critereLabel);
                        }
                        $criticite = !empty($this->indexedCriticites[$critereLabel]) ? $this->indexedCriticites[$critereLabel][0] : null;
                    }

                    if (null !== $criticite) {
                        if ($signalement) {
                            $signalement->addCriticite($criticite);
                        }
                    } else {
                        if (!isset($this->metadata['desordres_not_found'][$critereLabel])) {
                            $this->metadata['desordres_not_found'][$critereLabel] = 0;
                        }
                        ++$this->metadata['desordres_not_found'][$critereLabel];
                        continue;
                    }
                } catch (\Throwable $exception) {
                    $this->logger->error($critereLabel.' - '.$exception->getMessage());
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $dataMapped
     *
     * @return ArrayCollection<int, Suivi>
     */
    private function loadSuivi(Signalement $signalement, array $dataMapped): ArrayCollection
    {
        $suiviCollection = new ArrayCollection();
        if (isset($dataMapped['suivi']) && !empty($dataMapped['suivi'])) {
            foreach ($dataMapped['suivi'] as $suivi) {
                preg_match(self::REGEX_DATE_FORMAT_CSV, $suivi, $matches);
                $createdAt = array_shift($matches);
                $description = mb_trim(preg_replace(self::REGEX_DATE_FORMAT_CSV, '', $suivi));

                $suivi = $this->suiviManager->findOneBy([
                    'description' => $this->htmlSanitizer->sanitize($description),
                    'createdBy' => $this->userSystem,
                    'signalement' => $signalement,
                ]);

                if (null === $suivi) {
                    $suivi = $this->suiviManager->createSuivi(
                        signalement: $signalement,
                        description: $description,
                        type: Suivi::TYPE_PARTNER,
                        category: SuiviCategory::MESSAGE_PARTNER,
                        user: $this->userSystem,
                    );

                    if (null !== $createdAt) {
                        $suivi->setCreatedAt(new \DateTimeImmutable($createdAt));
                    }

                    $suiviCollection->add($suivi);
                }
            }
        }

        return $suiviCollection;
    }

    private function loadFiles(string $data, ?Signalement $signalement = null): void
    {
        if (empty($data)) {
            return;
        }

        $fileList = explode('|', $data);
        foreach ($fileList as $filename) {
            $exist = $this->entityManager->getRepository(File::class)->findOneBy(['filename' => $filename]);
            if ($exist) {
                continue;
            }
            if (!$this->fileStorage->fileExists($filename)) {
                $this->metadata['files_not_found'][$filename] = $filename;
                continue;
            }
            if (!$signalement) {
                continue;
            }

            $file = $this->fileManager->createOrUpdate(
                filename: $filename,
                title: $filename,
                signalement: $signalement,
            );
            $signalement->addFile($file);
            unset($file);
        }
    }
}
