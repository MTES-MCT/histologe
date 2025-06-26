<?php

namespace App\Command;

use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:push-idoss-dossier',
    description: 'Push dossier iDoss pour un signalement donné',
)]
class PushIdossDossierCommand extends Command
{
    public function __construct(
        private readonly AffectationRepository $affectationRepository,
        private readonly InterconnectionBus $interconnectionBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('uuid', null, 'Signalement Uuid');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid');

        if (!$uuid) {
            $io->error('L\'argument uuid est obligatoire.');

            return Command::FAILURE;
        }

        $affectations = $this->affectationRepository->findAffectationSubscribedToIdoss($uuid);

        if (!$affectations) {
            $io->warning('Aucun partenaire iDoss affecté à ce signalement.');

            return Command::FAILURE;
        }

        foreach ($affectations as $affectation) {
            $this->interconnectionBus->dispatch($affectation);
            $io->success(sprintf(
                '[%s] Dossier %s poussé vers iDoss',
                $affectation->getPartner()->getNom(),
                $affectation->getSignalement()->getUuid()
            ));
        }

        return Command::SUCCESS;
    }
}
