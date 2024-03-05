<?php

namespace App\Command;

use App\Entity\DesordrePrecision;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Manager\DesordreCritereManager;
use App\Manager\SignalementManager;
use App\Repository\DesordreCritereRepository;
use App\Repository\SignalementDraftRepository;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementProcessor;
use App\Utils\DataPropertyArrayFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-desordre-incurie',
    description: 'Recompute desordre incurie',
)]
class UpdateSignalementDesordreIncurieCommand extends Command
{
    public function __construct(
        private SignalementDraftRepository $signalementDraftRepository,
        private SignalementManager $signalementManager,
        private DesordreCritereManager $desordreCritereManager,
        private DesordreCritereRepository $desordreCritereRepository,
        private DesordreTraitementProcessor $desordreTraitementProcessor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $signalementDrafts = $this->signalementDraftRepository->findBy(
            ['status' => SignalementDraftStatus::EN_SIGNALEMENT]
        );

        if (empty($signalementDrafts)) {
            $io->warning('No signalementDraft transformend in signalement');

            return Command::SUCCESS;
        }
        $nbSignalementUpdated = 0;

        /** @var SignalementDraft $signalementDraft */
        foreach ($signalementDrafts as $signalementDraft) {
            $payload = $signalementDraft->getPayload();

            $filteredData = DataPropertyArrayFilter::filterByPrefix(
                $payload,
                ['desordres_logement_proprete']
            );
            if (\count($filteredData) > 0) {
                $io->info('signalementDraft '.$signalementDraft->getUuid().' has incurie information');
                // trouver le signalement correspondant
                $signalements = $signalementDraft->getSignalements();
                /** @var Signalement $signalement */
                foreach ($signalements as $signalement) {
                    $ajoutPrecision = false;
                    // vérifier s'il a les désordres liés à l'incurie
                    $io->info('signalement lié '.$signalement->getUuid());

                    $critereSlugDraft = $this->desordreCritereManager->getCriteresSlugsInDraft(
                        $filteredData,
                        ['desordres_logement_proprete']
                    );
                    $critereToLink = null;
                    if (\count($critereSlugDraft) > 0) {
                        $slugCritere = 'desordres_logement_proprete';
                        $critereToLink = $this->desordreCritereRepository->findOneBy(['slugCritere' => $slugCritere]);
                        if (null !== $critereToLink) {
                            if (!$signalement->hasDesordreCritere($critereToLink)) {
                                $signalement->addDesordreCritere($critereToLink);
                                $io->info('Ajout du critère '.$critereToLink->getLabelCritere());
                            } else {
                                $io->info('Le signalement a déjà le critère '.$critereToLink->getLabelCritere());
                            }
                            $desordrePrecisions = $this->desordreTraitementProcessor->findDesordresPrecisionsBy(
                                $critereToLink,
                                $payload
                            );
                            if (null !== $desordrePrecisions) {
                                /** @var DesordrePrecision $desordrePrecision */
                                foreach ($desordrePrecisions as $desordrePrecision) {
                                    if (null !== $desordrePrecision
                                    && !$signalement->hasDesordrePrecision($desordrePrecision)) {
                                        $signalement->addDesordrePrecision($desordrePrecision);
                                        $io->info('Ajout de la précision '.$desordrePrecision->getLabel());
                                        $ajoutPrecision = true;
                                    } else {
                                        $io->info('Le signalement a déjà la précision '.$desordrePrecision->getLabel());
                                    }
                                }
                                if ($ajoutPrecision) {
                                    ++$nbSignalementUpdated;
                                }
                            } else {
                                $io->info('Il n\'y a pas de précisions à lier ');
                            }
                        } else {
                            $io->info('Il n\'y a pas de critère à lier ');
                        }
                    }

                    if (null !== $critereToLink
                        && !$signalement->hasDesordreCategorie($critereToLink->getDesordreCategorie())) {
                        // lier la catégorie BO idoine
                        $signalement->addDesordreCategory($critereToLink->getDesordreCategorie());
                        $io->info('Ajout de la catégorie '.$critereToLink->getDesordreCategorie()->getLabel());
                    } elseif (null !== $critereToLink) {
                        $io->info(
                            'Le signalement a déjà la catégorie '.$critereToLink->getDesordreCategorie()->getLabel()
                        );
                    }
                    $this->signalementManager->persist($signalement);
                }
            }
        }
        $io->success(sprintf(
            '%s signalements updated.',
            $nbSignalementUpdated
        ));

        $this->signalementManager->flush();

        return Command::SUCCESS;
    }
}
