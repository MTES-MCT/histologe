<?php

namespace App\Service\Signalement\Import;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\TagManager;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;

class SignalementImportLoader
{
    private array $metadata = [
        'count_signalement' => 0,
    ];

    public function __construct(
        private SignalementImportMapper $signalementImportMapper,
        private SignalementManager $signalementManager,
        private TagManager $tagManager,
        private AffectationManager $affectationManager,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    private const SITUATIONS = [
        'Sécurité des occupants',
        'Etat et propreté du logement',
        'confort du logement',
        'Etat du bâtiment',
        'Les espaces de vie',
        'Vie commune et voisinage',
    ];

    public function load(Territory $territory, array $data, array $headers): void
    {
        $countSignalement = 0;

        foreach ($data as $item) {
            $dataMapped = $this->signalementImportMapper->map($headers, $item);
            if (!empty($dataMapped)) {
                ++$countSignalement;
                $signalement = $this->signalementManager->createOrGet($territory, $dataMapped, true);
                $signalement = $this->loadTags($signalement, $territory, $dataMapped);
                $this->loadAffectation($signalement, $territory, $dataMapped);

                foreach (self::SITUATIONS as $situation) {
                    $signalement = $this->loadSignalementSituation($signalement, $dataMapped, $situation);
                }

                $this->signalementManager->persist($signalement);
                $this->metadata['count_signalement'] = $countSignalement;
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

    private function loadAffectation(Signalement $signalement, Territory $territory, array $dataMapped): void
    {
        if (isset($dataMapped['partners']) && !empty($dataMapped['partners'])) {
            $partnersName = explode(',', $dataMapped['partners']);
            foreach ($partnersName as $partnerName) {
                $partner = $this->entityManager->getRepository(Partner::class)->findOneBy([
                    'nom' => $partnerName,
                    'territory' => $territory,
                ]);

                if (null !== $partner) {
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $partner?->getUsers()?->first()
                    );

                    if (!empty($dataMapped['motifCloture'])) {
                        $affectation
                            ->setStatut(Affectation::STATUS_CLOSED)
                            ->setAnsweredAt(new \DateTimeImmutable())
                            ->setMotifCloture($dataMapped['motifCloture']);
                    } else {
                        $affectation->setStatut(Affectation::STATUS_ACCEPTED);
                    }

                    $this->affectationManager->persist($affectation);
                }
            }
        }
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
}
