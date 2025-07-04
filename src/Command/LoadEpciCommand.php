<?php

namespace App\Command;

use App\Entity\Commune;
use App\Entity\Epci;
use App\Repository\CommuneRepository;
use App\Repository\EpciRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:load-epci',
    description: 'Load EPCI from geo.api.gouv.fr',
)]
class LoadEpciCommand extends Command
{
    public const string API_EPCI_ALL_URL = 'https://geo.api.gouv.fr/epcis?fields=nom';
    public const string API_EPCI_COMMUNE_URL = 'https://geo.api.gouv.fr/epcis/%d/communes?fields=nom,codesPostaux';
    /** @var array<Epci> */
    private array $epcis = [];
    /** @var array<Commune> */
    private array $communes = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CommuneRepository $communeRepository,
        private readonly EpciRepository $epciRepository,
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
                $epciCommunesUrl = \sprintf(self::API_EPCI_COMMUNE_URL, $epciItem['code'])
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
                $io->error(\sprintf('API failed for: %s', $epciCommunesUrl));
            }
            $this->entityManager->persist($epci);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->entityManager->flush();
        $nbCommunesWithECPI = $this->communeRepository->count([]);
        $nbCommunesWithoutECPI = $this->communeRepository->count(['epci' => null]);
        $io->success(\sprintf(
            'EPCI loaded with %d communes that belong to EPCI',
            $nbCommunesWithECPI - $nbCommunesWithoutECPI
        ));
        if ($nbCommunesWithoutECPI > 0) {
            $io->warning(\sprintf(
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
