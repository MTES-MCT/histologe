<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Factory\Interconnection\Esabora\DossierMessageSISHFactory;
use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Esabora\Handler\DossierAdresseServiceHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        private readonly InterconnectionBus $esaboraBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'action to target (push_dossier_adresse, push_dossier, push_dossier_personne)');
        $this->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Delay before checking if there are still some failed dossier', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $delay = $input->getOption('delay');

        $this->io->title('<info>Recherches des dossiers de SISH avec l\'action '.$action.' en erreur </info>');

        $failedDossiers = $this->jobEventRepository->findFailedEsaboraDossierByPartnerTypeByAction(PartnerType::ARS, $action);

        if (0 === \count($failedDossiers)) {
            $this->io->success('Aucun dossier en erreur');

            return Command::SUCCESS;
        }
        $this->io->text('<info>Nombre de dossier ARS en erreur </info>'.\count($failedDossiers));
        /** @var JobEvent $failedDossier */
        foreach ($failedDossiers as $failedDossier) {
            $signalementId = $failedDossier->getSignalementId();
            $partnerId = $failedDossier->getPartnerId();

            $signalement = $this->signalementRepository->find($signalementId);

            $affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partnerId) {
                return $affectation->getPartner()->getId() === $partnerId;
            })->first();

            if ($affectation instanceof Affectation) {
                $this->io->text('Renvoi du dossier pour le signalement '.$signalementId.' et le partenaire '.$partnerId);
                $this->esaboraBus->dispatch($affectation);
            } else {
                $this->io->text('Pas d\'affectation trouvée pour le signalement '.$signalementId.' par le partenaire '.$partnerId);
            }
        }
        sleep($delay);
        $failedDossiersAfter = $this->jobEventRepository->findFailedEsaboraDossierByPartnerTypeByAction(PartnerType::ARS, $action);
        $this->io->section('<info>Nombre de dossier ARS en erreur restants après traitement </info>'.\count($failedDossiersAfter));

        return Command::SUCCESS;
    }
}
