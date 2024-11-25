<?php

namespace App\Command;

use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\DataGouv\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:init-id-ban',
    description: 'Search BAN ID when missing in Adresse Occupant of Signalement',
)]
class InitIdBanCommand extends Command
{
    private const float SCORE_IF_ACCEPTED = 0.85;
    private const int BATCH_SIZE = 20;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly AddressService $addressService,
        private readonly SignalementRepository $signalementRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $territory = $this->territoryRepository->findBy(['zip' => '44']);
        $listSignalementBanIdNull = $this->signalementRepository->findBy(['banIdOccupant' => null, 'territory' => $territory]);

        $nb = 0;
        $progressBar = new ProgressBar($output, \count($listSignalementBanIdNull));
        $progressBar->start();
        /** @var Signalement $signalement */
        foreach ($listSignalementBanIdNull as $signalement) {
            $bestAddressResult = $this->addressService->getAddress($signalement->getAddressCompleteOccupant());
            if ($bestAddressResult->getScore() > self::SCORE_IF_ACCEPTED) {
                $signalement->setBanIdOccupant($bestAddressResult->getBanId());
            }

            $this->entityManager->persist($signalement);
            ++$nb;
            if (0 === $nb % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $nbSignalementWithoutBanId = $this->signalementRepository->count(['banIdOccupant' => null]);
        $io->success(\sprintf(
            'BAN IDs have been initialized, but %s signalements remain with no BAN ID',
            $nbSignalementWithoutBanId
        ));

        return Command::SUCCESS;
    }
}
