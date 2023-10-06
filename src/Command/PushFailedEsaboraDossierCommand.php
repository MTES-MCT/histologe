<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Factory\Esabora\DossierMessageSISHFactory;
use App\Messenger\EsaboraBus;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Esabora\Handler\DossierAdresseServiceHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:push-failed-esabora-dossier',
    description: 'Push failed Esabora dossier',
)]
class PushFailedEsaboraDossierCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly JobEventRepository $jobEventRepository,
        private readonly DossierMessageSISHFactory $dossierMessageFactory,
        private readonly AffectationRepository $affectationRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly DossierAdresseServiceHandler $dossierAdresseServiceHandler,
        private readonly EsaboraBus $esaboraBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $failedDossiers = $this->jobEventRepository->findFailedEsaboraDossierByPartnerTypeByAction(PartnerType::ARS, 'push_dossier_adresse');

        $this->io->section('<info>Nombre de dossier ARS en erreur </info>'.\count($failedDossiers));
        /** @var JobEvent $failedDossier */
        foreach ($failedDossiers as $failedDossier) {
            $signalementId = $failedDossier->getSignalementId();
            $partnerId = $failedDossier->getPartnerId();
            $this->io->text($signalementId.' - '.$partnerId);

            $signalement = $this->signalementRepository->findOneBy(['id' => $signalementId]);

            $affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partnerId) {
                return $affectation->getPartner()->getId() === $partnerId;
            })->first();

            $this->esaboraBus->dispatch($affectation);

            // TODO :  pouvoir identifier si ça a marché ?

            // TODO : seul le push_dossier_adresse fonctionne, je pense parce que sasId  reste à null et n'est pas mis à jour
        }

        return Command::SUCCESS;
    }
}
