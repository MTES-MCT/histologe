<?php

namespace App\Service\Signalement\Import;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\TagManager;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementImportLoader
{
    private const FLUSH_COUNT = 1000;
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
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function load(Territory $territory, array $data, array $headers): void
    {
        $countSignalement = 0;

        $this->userSystem = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => $this->parameterBag->get('user_system_email'), ]
        );

        foreach ($data as $item) {
            $dataMapped = $this->signalementImportMapper->map($headers, $item);
            if (!empty($dataMapped)) {
                ++$countSignalement;
                $signalement = $this->signalementManager->createOrUpdate($territory, $dataMapped, true);
                $signalement = $this->loadTags($signalement, $territory, $dataMapped);
                foreach (self::SITUATIONS as $situation) {
                    $signalement = $this->loadSignalementSituation($signalement, $dataMapped, $situation);
                }

                $affectationCollection = $this->loadAffectation($signalement, $territory, $dataMapped);
                foreach ($affectationCollection as $affectation) {
                    $signalement->addAffectation($affectation);
                }

                $suiviCollection = $this->loadSuivi($signalement, $dataMapped);
                foreach ($suiviCollection as $suivi) {
                    $signalement->addSuivi($suivi);
                }

                $this->metadata['count_signalement'] = $countSignalement;
                if (0 === $countSignalement % self::FLUSH_COUNT) {
                    $this->logger->info(sprintf('in progress - %s signalements saved', $countSignalement));
                    $this->signalementManager->flush();
                } else {
                    $this->signalementManager->persist($signalement);
                }
            }
        }
        $this->signalementManager->flush();
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
            $partnersName = explode(',', $dataMapped['partners']);
            foreach ($partnersName as $partnerName) {
                $partner = $this->entityManager->getRepository(Partner::class)->findOneBy([
                    'nom' => trim($partnerName),
                    'territory' => $territory,
                ]);

                if (null !== $partner) {
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $partner?->getUsers()?->first()
                    );

                    if ($affectation instanceof Affectation) {
                        $affectation
                            ->setCreatedAt($dataMapped['createdAt'])
                            ->setAnsweredAt($dataMapped['createdAt']);
                        if (!empty($dataMapped['motifCloture'])) {
                            $affectation = $this->affectationManager->closeAffectation(
                                $affectation,
                                $this->userSystem,
                                $dataMapped['motifCloture']
                            );
                        } else {
                            $affectation->setStatut(Affectation::STATUS_ACCEPTED);
                        }
                        $affectationCollection->add($affectation);
                    }
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
                        $criticite = $criticiteRepository->findByLabel(trim($critereLabel));
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
                    $suivi
                        ->setDescription($description)
                        ->setCreatedAt(new \DateTimeImmutable($createdAt));

                    $suiviCollection->add($suivi);
                }
            }
        }

        return $suiviCollection;
    }
}
