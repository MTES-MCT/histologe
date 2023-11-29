<?php

namespace App\Command;

use App\Entity\Signalement;
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
    public function __construct(
        private AddressService $addressService,
        private TerritoryRepository $territoryRepository,
        private SignalementRepository $signalementRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('zip', null, InputOption::VALUE_OPTIONAL, 'Territory zip to target')
            ->addOption('uuid', null, InputOption::VALUE_OPTIONAL, 'UUID du signalement');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $zip = $input->getOption('zip');
        $uuid = $input->getOption('uuid');

        if ($uuid) {
            $signalements = $this->signalementRepository->findBy(['uuid' => $uuid]);
        } else {
            $territory = $this->territoryRepository->findOneBy(['zip' => $zip]);
            $signalements = $this->signalementRepository->findWithNoGeolocalisation($territory);
        }

        if (empty($signalements)) {
            $io->warning('No address signalement to compute with BAN API');

            return Command::SUCCESS;
        }

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $street = sprintf('%s %s %s',
                $signalement->getAdresseOccupant(),
                $signalement->getCpOccupant(),
                $signalement->getVilleOccupant()
            );
            $address = $this->addressService->getAddress($street);
            $signalement->setInseeOccupant($address->getInseeCode());

            if (empty($signalement->getCpOccupant())) {
                $signalement->setCpOccupant($address->getZipCode());
            }
            if (empty($signalement->getGeoloc())) {
                $signalement->setGeoloc([
                    'lat' => $address->getLatitude(),
                    'lng' => $address->getLongitude(),
                ]);
            }
            $this->signalementRepository->save($signalement);
            $io->success(sprintf('Signalement %s updated.%sAddress : %sCode insee : %sGPS : [%s, %s]',
                $signalement->getUuid(),
                \PHP_EOL,
                $address->getLabel().\PHP_EOL,
                $address->getInseeCode().\PHP_EOL,
                $address->getLongitude(),
                $address->getLatitude()));
        }

        $this->signalementRepository->save($signalement, true);

        return Command::SUCCESS;
    }
}
