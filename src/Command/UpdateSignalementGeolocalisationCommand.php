<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\DataGouv\AddressService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-geolocalisation',
    description: 'Recompute geolocalisation signalement data for missing code insee signalement',
)]
class UpdateSignalementGeolocalisationCommand extends Command
{
    public const int BATCH_SIZE = 20;

    public function __construct(
        private readonly AddressService $addressService,
        private readonly TerritoryRepository $territoryRepository,
        private readonly SignalementManager $signalementManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('zip', null, InputOption::VALUE_OPTIONAL, 'Territory zip to target')
            ->addOption('uuid', null, InputOption::VALUE_OPTIONAL, 'UUID du signalement')
            ->addOption('from_created_at', null, InputOption::VALUE_OPTIONAL, 'Get signalements data from created_at');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $zip = $input->getOption('zip');
        $uuid = $input->getOption('uuid');
        $fromCreatedAt = $input->getOption('from_created_at');
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->signalementManager->getRepository();
        $signalements = null;
        if ($uuid) {
            $signalements = $this->signalementManager->findBy(['uuid' => $uuid]);
        } elseif (!empty($zip)) {
            $territory = $this->territoryRepository->findOneBy(['zip' => $zip]);
            $signalements = $signalementRepository->findWithNoGeolocalisation($territory);
        } elseif (!empty($fromCreatedAt)) {
            $fromCreatedAt = \DateTimeImmutable::createFromFormat('Y-m-d', $fromCreatedAt);
            if (false !== $fromCreatedAt) {
                $signalements = $signalementRepository->findSignalementsBetweenDates($fromCreatedAt, new \DateTimeImmutable());
            }
        }

        if (empty($signalements)) {
            $io->warning('No address signalement to compute with BAN API');

            return Command::SUCCESS;
        }

        $i = 0;
        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $address = $this->addressService->getAddress($signalement->getAddressCompleteOccupant());
            $this->signalementManager->updateAddressOccupantFromAddress($signalement, $address);

            $io->success(\sprintf('Signalement %s updated.%sAddress : %sCode insee : %sGPS : [%s, %s]',
                $signalement->getUuid(),
                \PHP_EOL,
                $address->getLabel().\PHP_EOL,
                $address->getInseeCode().\PHP_EOL,
                $address->getLongitude(),
                $address->getLatitude()));

            if (0 === $i % self::BATCH_SIZE) {
                $this->signalementManager->flush();
            }
            ++$i;
        }

        $this->signalementManager->flush();

        return Command::SUCCESS;
    }
}
