<?php

namespace App\Command;

use App\Entity\DesordrePrecision;
use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Repository\DesordrePrecisionRepository;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-desordres',
    description: 'Recompute composition desordres for signalement',
)]
class UpdateSignalementDesordresCommand extends Command
{
    public function __construct(
        private SignalementManager $signalementManager,
        private DesordrePrecisionRepository $desordrePrecisionRepository,
        private DesordreCompositionLogementLoader $desordreCompositionLogementLoader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $signalements = $this->addSignalementsByDesordrePrecisionSlug(
            'desordres_type_composition_logement_piece_unique_superficie'
        );
        $signalements = array_merge($signalements, $this->addSignalementsByDesordrePrecisionSlug(
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9'
        ));
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_piece_unique_hauteur')
        );
        $signalements = array_merge($signalements, $this->addSignalementsByDesordrePrecisionSlug(
            'desordres_type_composition_logement_plusieurs_pieces_hauteur'
        ));
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_cuisine_collective_oui')
        );
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_cuisine_collective_non')
        );
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_douche_collective_oui')
        );
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_douche_collective_non')
        );
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_wc_collectif_oui')
        );
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_wc_collectif_non')
        );
        $signalements = array_merge(
            $signalements,
            $this->addSignalementsByDesordrePrecisionSlug('desordres_type_composition_logement_wc_cuisine_ensemble')
        );

        if (empty($signalements)) {
            $io->warning('No signalement with one of this desordrePrecision ');

            return Command::SUCCESS;
        }
        $signalements = array_unique($signalements);
        $io->info(\count($signalements).' signalements with one of this desordrePrecision');

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            if (null === $signalement->getCreatedFrom()) {
                foreach ($signalement->getDesordreCategories() as $desordreCategorie) {
                    $signalement->removeDesordreCategory($desordreCategorie);
                }
                foreach ($signalement->getDesordreCriteres() as $desordreCritere) {
                    $signalement->removeDesordreCritere($desordreCritere);
                }
                foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
                    $signalement->removeDesordrePrecision($desordrePrecision);
                }
            } else {
                $this->desordreCompositionLogementLoader->load(
                    $signalement,
                    $signalement->getTypeCompositionLogement()
                );
            }

            $this->signalementManager->persist($signalement);

            $io->success(sprintf(
                'Signalement %s updated.%sNb desordrePrecisions : %sNb desordreCriteres: %sNb desordreCategories: %s',
                $signalement->getUuid(),
                \PHP_EOL,
                \count($signalement->getDesordrePrecisions()).\PHP_EOL,
                \count($signalement->getDesordreCriteres()).\PHP_EOL,
                \count($signalement->getDesordreCategories()).\PHP_EOL,
            ));
        }

        $this->signalementManager->flush();

        return Command::SUCCESS;
    }

    private function addSignalementsByDesordrePrecisionSlug(string $desordrePrecisionSlug): array
    {
        /** @var DesordrePrecision $desordrePrecision */
        $desordrePrecision = $this->desordrePrecisionRepository->findOneBy(
            ['desordrePrecisionSlug' => $desordrePrecisionSlug]
        );

        return $desordrePrecision->getSignalement()->toArray();
    }
}
