<?php

namespace App\Service\Import\Signalement;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\MotifCloture;
use App\Entity\File;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\FileManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\TagManager;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use App\Service\ImageManipulationHandler;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    private array $metadata = [
        'count_signalement' => 0,
        'partners_not_found' => [],
        'motif_cloture_not_found' => [],
        'files_not_found' => [],
    ];

    private User|null $userSystem = null;

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
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function load(Territory $territory, array $data, array $headers, ?OutputInterface $output = null): void
    {
        $countSignalement = 0;
        if ($output) {
            $progressBar = new ProgressBar($output);
            $progressBar->start(\count($data));
        }

        $this->userSystem = $this->entityManager->getRepository(User::class)->findOneBy(
            [
                'email' => $this->parameterBag->get('user_system_email'), ]
        );

        foreach ($data as $item) {
            $dataMapped = $this->signalementImportMapper->map($headers, $item);
            if (!empty($dataMapped)) {
                ++$countSignalement;
                if ($output) {
                    $progressBar->advance();
                }
                $signalement = $this->signalementManager->createOrUpdate($territory, $dataMapped, true);
                $signalement = $this->loadTags($signalement, $territory, $dataMapped);
                foreach (self::SITUATIONS as $situation) {
                    $signalement = $this->loadSignalementSituation($signalement, $dataMapped, $situation);
                }

                $signalement->setScore($this->criticiteCalculator->calculate($signalement));
                $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);

                $affectationCollection = $this->loadAffectation($signalement, $territory, $dataMapped);
                foreach ($affectationCollection as $affectation) {
                    $signalement->addAffectation($affectation);
                }

                $suiviCollection = $this->loadSuivi($signalement, $dataMapped);
                foreach ($suiviCollection as $suivi) {
                    $signalement->addSuivi($suivi);
                }

                $this->loadFiles($signalement, File::INPUT_NAME_PHOTOS, $dataMapped);
                $this->loadFiles($signalement, File::INPUT_NAME_DOCUMENTS, $dataMapped);

                $this->metadata['count_signalement'] = $countSignalement;
                if (0 === $countSignalement % self::FLUSH_COUNT) {
                    $this->logger->info(sprintf('in progress - %s signalements saved', $countSignalement));
                    $this->signalementManager->flush();
                } else {
                    $this->signalementManager->persist($signalement);
                    unset($signalement);
                }
                if ($dataMapped['motifCloture'] && !MotifCloture::tryFrom($dataMapped['motifCloture'])) {
                    if (!isset($this->metadata['motif_cloture_not_found'][$dataMapped['motifCloture']])) {
                        $this->metadata['motif_cloture_not_found'][$dataMapped['motifCloture']] = 1;
                    }
                    ++$this->metadata['motif_cloture_not_found'][$dataMapped['motifCloture']];
                }
            }
        }

        $this->signalementManager->flush();
        if ($output) {
            $progressBar->finish();
        }
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function loadTags(Signalement $signalement, Territory $territory, array $dataMapped): Signalement
    {
        if (isset($dataMapped['tags']) && !empty($dataMapped['tags'])) {
            $tag = $this->tagManager->createOrGet($territory, $dataMapped['tags']);
            $signalement->addTag($tag);
        }

        return $signalement;
    }

    private function loadAffectation(Signalement $signalement, Territory $territory, array $dataMapped): ArrayCollection
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
                $partnerNameCleaned = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $partnerName)); // remove non printable chars
                $partner = $this->entityManager->getRepository(Partner::class)->findOneBy([
                    'nom' => $partnerNameCleaned,
                    'territory' => $territory,
                ]);
                if (!$partner) {
                    if (!isset($this->metadata['partners_not_found'][$partnerNameCleaned])) {
                        $this->metadata['partners_not_found'][$partnerNameCleaned] = 1;
                    }
                    ++$this->metadata['partners_not_found'][$partnerNameCleaned];
                    continue;
                }

                $affectation = $this->affectationManager->createAffectationFrom(
                    $signalement,
                    $partner,
                    $partner->getUsers()->isEmpty() ? null : $partner->getUsers()->first(),
                );
                if ($affectation instanceof Affectation) {
                    $affectation
                        ->setCreatedAt($dataMapped['createdAt'])
                        ->setAnsweredAt($dataMapped['createdAt']);
                    if (MotifCloture::tryFrom($dataMapped['motifCloture'])) {
                        $affectation = $this->affectationManager->closeAffectation(
                            $affectation,
                            $this->userSystem,
                            MotifCloture::tryFrom($dataMapped['motifCloture'])
                        );
                    } else {
                        $affectation->setStatut(Affectation::STATUS_ACCEPTED);
                    }
                    $affectationCollection->add($affectation);
                }
            }
        }

        return $affectationCollection;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function loadSignalementSituation(
        Signalement $signalement,
        array $dataMapped,
        string $situation
    ): Signalement {
        if (isset($dataMapped[$situation]) && !empty($dataMapped[$situation])) {
            foreach ($dataMapped[$situation] as $critereLabel => $etat) {
                /** @var CritereRepository $critereRepository */
                $critereRepository = $this->entityManager->getRepository(Critere::class);

                /** @var Critere $critere */
                $critere = $critereRepository->findByLabel(trim($critereLabel));
                try {
                    if (null !== $critere) {
                        /** @var Criticite $criticite */
                        $criticite = $critere->getCriticites()->filter(function (Criticite $criticite) use ($etat) {
                            return $criticite->getScore() === Criticite::ETAT_LABEL[trim($etat)];
                        })->first();
                    } else {
                        /** @var CriticiteRepository $criticiteRepository */
                        $criticiteRepository = $this->entityManager->getRepository(Criticite::class);
                        $criticites = $criticiteRepository->findByLabel(trim($critereLabel));
                        $criticite = !empty($criticites) ? $criticites[0] : null;
                        $critere = $criticite?->getCritere();
                    }

                    if (null !== $criticite) {
                        $signalement
                            ->addCriticite($criticite)
                            ->addSituation($critere->getSituation())
                            ->addCritere($critere);
                    }
                } catch (\Throwable $exception) {
                    $this->logger->error($critereLabel.' - '.$exception->getMessage());
                }
            }
        }

        return $signalement;
    }

    /**
     * @throws \Exception
     */
    private function loadSuivi(Signalement $signalement, array $dataMapped): ArrayCollection
    {
        $suiviCollection = new ArrayCollection();
        if (isset($dataMapped['suivi']) && !empty($dataMapped['suivi'])) {
            foreach ($dataMapped['suivi'] as $suivi) {
                preg_match(self::REGEX_DATE_FORMAT_CSV, $suivi, $matches);
                $createdAt = array_shift($matches);
                $description = trim(preg_replace(self::REGEX_DATE_FORMAT_CSV, '', $suivi));

                $suivi = $this->suiviManager->findOneBy([
                    'description' => $description,
                    'createdBy' => $this->userSystem,
                    'signalement' => $signalement,
                ]);

                if (null === $suivi) {
                    $suivi = $this->suiviManager->createSuivi($this->userSystem, $signalement, [], false);
                    $suivi->setDescription($description);
                    if (null !== $createdAt) {
                        $suivi->setCreatedAt(new \DateTimeImmutable($createdAt));
                    }

                    $suiviCollection->add($suivi);
                }
            }
        }

        return $suiviCollection;
    }

    private function loadFiles(Signalement $signalement, string $colName, array $dataMapped): void
    {
        if (empty($dataMapped[$colName])) {
            return;
        }

        $fileList = explode('|', $dataMapped[$colName]);
        foreach ($fileList as $filename) {
            $exist = $this->entityManager->getRepository(File::class)->findOneBy(['filename' => $filename]);
            if ($exist) {
                continue;
            }
            if (!$this->fileStorage->fileExists($filename)) {
                $this->metadata['files_not_found'][$filename] = $filename;
                continue;
            }
            $fileType = File::FILE_TYPE_DOCUMENT;
            if (\in_array($this->fileStorage->mimeType($filename), ImageManipulationHandler::IMAGE_MIME_TYPES)) {
                $fileType = File::FILE_TYPE_PHOTO;
            }

            $file = $this->fileManager->createOrUpdate(
                filename: $filename,
                title: $filename,
                type: $fileType,
                signalement: $signalement,
            );
            $signalement->addFile($file);
            unset($file);
        }
    }
}
