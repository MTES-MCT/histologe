<?php

namespace App\Command;

use App\Entity\Epci;
use App\Repository\CommuneRepository;
use App\Repository\EpciRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:load-epci',
    description: 'Load EPCI from geo.api.gouv.fr',
)]
class LoadEpciCommand extends Command
{
    public const API_EPCI_ALL_URL = 'https://geo.api.gouv.fr/epcis?fields=nom';
    public const API_EPCI_COMMUNE_URL = 'https://geo.api.gouv.fr/epcis/%d/communes?fields=nom,codesPostaux';
    private array $epcis = [];
    private array $communes = [];

    public function __construct(
        private HttpClientInterface $httpClient,
        private TerritoryRepository $territoryRepository,
        private CommuneRepository $communeRepository,
        private EpciRepository $epciRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->initDataFromRepository();

        $response = $this->httpClient->request('GET', self::API_EPCI_ALL_URL);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $io->error('API failed');

            return Command::FAILURE;
        }

        $epciList = json_decode($response->getContent(), true);
        $progressBar = new ProgressBar($output, \count($epciList));
        $progressBar->start();
        foreach ($epciList as $epciItem) {
            $epci = $this->epcis[$epciItem['code']] ?? null;
            if (!$epci) {
                $epci = (new Epci())
                    ->setNom($epciItem['nom'])
                    ->setCode($epciItem['code']);
            }
            $response = $this->httpClient->request(
                'GET',
                $epciCommunesUrl = sprintf(self::API_EPCI_COMMUNE_URL, $epciItem['code'])
            );

            if (Response::HTTP_OK === $response->getStatusCode()) {
                $communeList = json_decode($response->getContent(), true);
                foreach ($communeList as $communeItem) {
                    foreach ($communeItem['codesPostaux'] as $codePostal) {
                        if (isset($this->communes[$communeItem['nom']][$codePostal])) {
                            $epci->addCommune($this->communes[$communeItem['nom']][$codePostal]);
                        }
                    }
                }
            } else {
                $io->error(sprintf('API failed for: %s', $epciCommunesUrl));
            }
            $this->entityManager->persist($epci);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->entityManager->flush();
        $nbCommunesWithECPI = $this->communeRepository->count([]);
        $nbCommunesWithoutECPI = $this->communeRepository->count(['epci' => null]);
        $io->success(sprintf(
            'EPCI loaded with %d communes that belong to EPCI',
            $nbCommunesWithECPI - $nbCommunesWithoutECPI
        ));
        if ($nbCommunesWithoutECPI > 0) {
            $io->warning(sprintf(
                '%d communes code postal might be obsolete.',
                $nbCommunesWithoutECPI
            ));
        }

        return Command::SUCCESS;
    }

    private function initDataFromRepository(): void
    {
        $epcis = $this->epciRepository->findAll();
        foreach ($epcis as $epci) {
            $this->epcis[$epci->getCode()] = $epci;
        }

        $communes = $this->communeRepository->findAll();
        foreach ($communes as $commune) {
            $this->communes[$commune->getNom()][$commune->getCodePostal()] = $commune;
        }
    }
}
