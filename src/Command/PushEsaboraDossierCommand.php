<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use App\Repository\TerritoryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:push-esabora-dossier',
    description: 'Push dossier SI-SH or SCHS',
)]
class PushEsaboraDossierCommand extends Command
{
    public const TERRITORY_NOT_ALLOWED = ['13', '06'];

    public function __construct(
        private AffectationRepository $affectationRepository,
        private TerritoryRepository $territoryRepository,
        private InterconnectionBus $esaboraBus,
        #[Autowire(param: 'kernel.environment')]
        private string $env
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('service_type', InputArgument::REQUIRED, 'sish or schs')
            ->addOption('zip', null, InputOption::VALUE_OPTIONAL, 'Territory zip to target')
            ->addOption('uuid', null, InputOption::VALUE_OPTIONAL, 'Signalement Uuid');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serviceType = $input->getArgument('service_type');
        $zip = $input->getOption('zip');
        $uuid = $input->getOption('uuid');

        if (!\in_array($serviceType, ['sish', 'schs'])) {
            $io->error('Le service_type doit être soit "sish" soit "schs".');

            return Command::FAILURE;
        }

        $affectations = null;
        if ($uuid) {
            $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
                partnerType: 'sish' === $serviceType ? PartnerType::ARS : PartnerType::COMMUNE_SCHS,
                uuidSignalement: $uuid
            );
        } elseif ($zip) {
            $territory = $this->territoryRepository->findOneBy(['zip' => $zip, 'isActive' => 1]);
            if (null === $territory) {
                $io->error('Territory does not exist or is not active');

                return Command::FAILURE;
            } elseif ('prod' === $this->env && \in_array($zip, self::TERRITORY_NOT_ALLOWED)) {
                $io->error('It is not allowed to synchronize 13 and 06 in the production environment');

                return Command::FAILURE;
            }

            $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
                partnerType: 'sish' === $serviceType ? PartnerType::ARS : PartnerType::COMMUNE_SCHS,
                isSynchronized: false,
                territory: $territory
            );
        }

        if (!$affectations) {
            $io->warning('No dossier to pushed to Esabora '.$serviceType);

            return Command::FAILURE;
        }

        foreach ($affectations as $affectation) {
            $this->esaboraBus->dispatch($affectation);
            $io->success(sprintf(
                '[%s] Dossier %s pushed to esabora',
                $affectation->getPartner()->getType()->value,
                $affectation->getSignalement()->getUuid()
            ));
        }

        return Command::SUCCESS;
    }
}
