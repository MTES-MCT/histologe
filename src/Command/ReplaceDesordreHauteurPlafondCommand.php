<?php

namespace App\Command;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:replace-desordre-hauteur-plafond',
    description: 'Replace desordre hauteur plafond',
)]
class ReplaceDesordreHauteurPlafondCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $newDesordrePrecisionHauteurPlafond = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => 'desordres_logement_lumiere_plafond_trop_bas_toutes_pieces']);
        $newDesordreCritereHauteurPlafond = $newDesordrePrecisionHauteurPlafond->getDesordreCritere();

        $oldDesordrePrecisionHauteurPlafondPieceUnique = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => 'desordres_type_composition_logement_piece_unique_hauteur']);
        $oldDesordreCritereHauteurPlafondPieceUnique = $oldDesordrePrecisionHauteurPlafondPieceUnique->getDesordreCritere();
        $oldDesordrePrecisionHauteurPlafondPlusieursPieces = $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => 'desordres_type_composition_logement_plusieurs_pieces_hauteur']);
        $oldDesordreCritereHauteurPlafondPlusieursPieces = $oldDesordrePrecisionHauteurPlafondPlusieursPieces->getDesordreCritere();

        // get list of signalement linked to desordre precision with desordres_type_composition_logement_piece_unique_hauteur or desordres_type_composition_logement_plusieurs_pieces_hauteur
        $signalementIds = $this->entityManager->getConnection()->fetchFirstColumn('
            SELECT DISTINCT dps.signalement_id FROM desordre_precision_signalement dps WHERE dps.desordre_precision_id IN (
                SELECT id FROM desordre_precision WHERE desordre_precision_slug IN (
                    "desordres_type_composition_logement_piece_unique_hauteur",
                    "desordres_type_composition_logement_plusieurs_pieces_hauteur"
                )
            )
        ');

        $progressBar = $io->createProgressBar(count($signalementIds));
        $progressBar->start();
        foreach ($signalementIds as $signalementId) {
            $desordreCritereSignalements = $this->entityManager->getConnection()->fetchAllAssociative(
                'SELECT * FROM desordre_critere_signalement WHERE signalement_id = :signalementId',
                ['signalementId' => $signalementId]
            );
            $desordrePrecisionSignalements = $this->entityManager->getConnection()->fetchAllAssociative(
                'SELECT * FROM desordre_precision_signalement WHERE signalement_id = :signalementId',
                ['signalementId' => $signalementId]
            );
            $hasNewCritere = false;
            $hasNewPrecision = false;
            foreach ($desordreCritereSignalements as $desordreCritereSignalement) {
                if ($desordreCritereSignalement['desordre_critere_id'] === $newDesordreCritereHauteurPlafond->getId()) {
                    $hasNewCritere = true;
                }
            }
            foreach ($desordrePrecisionSignalements as $desordrePrecisionSignalement) {
                if ($desordrePrecisionSignalement['desordre_precision_id'] === $newDesordrePrecisionHauteurPlafond->getId()) {
                    $hasNewPrecision = true;
                }
            }
            if (!$hasNewCritere) {
                $this->entityManager->getConnection()->insert('desordre_critere_signalement', [
                    'desordre_critere_id' => $newDesordreCritereHauteurPlafond->getId(),
                    'signalement_id' => $signalementId,
                ]);
            }
            if (!$hasNewPrecision) {
                $this->entityManager->getConnection()->insert('desordre_precision_signalement', [
                    'desordre_precision_id' => $newDesordrePrecisionHauteurPlafond->getId(),
                    'signalement_id' => $signalementId,
                ]);
            }
            // Remove old desordre precision and critere
            $this->entityManager->getConnection()->delete('desordre_precision_signalement', [
                'desordre_precision_id' => $oldDesordrePrecisionHauteurPlafondPieceUnique->getId(),
                'signalement_id' => $signalementId,
            ]);
            $this->entityManager->getConnection()->delete('desordre_precision_signalement', [
                'desordre_precision_id' => $oldDesordrePrecisionHauteurPlafondPlusieursPieces->getId(),
                'signalement_id' => $signalementId,
            ]);
            $this->entityManager->getConnection()->delete('desordre_critere_signalement', [
                'desordre_critere_id' => $oldDesordreCritereHauteurPlafondPieceUnique->getId(),
                'signalement_id' => $signalementId,
            ]);
            $this->entityManager->getConnection()->delete('desordre_critere_signalement', [
                'desordre_critere_id' => $oldDesordreCritereHauteurPlafondPlusieursPieces->getId(),
                'signalement_id' => $signalementId,
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->success('Les critères et précisions de désordre "Hauteur Plafond" ont été remplacé sur '.count($signalementIds).' signalements.');

        return Command::SUCCESS;
    }
}
