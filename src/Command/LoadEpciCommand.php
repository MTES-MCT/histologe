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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:load-epci',
    description: 'Load EPCI from geo.api.gouv.fr',
)]
class LoadEpciCommand extends Command
{
    public const API_EPCI_ALL_URL = 'https://geo.api.gouv.fr/epcis?fields=nom';
    public const API_EPCI_COMMUNE_URL = 'https://geo.api.gouv.fr/epcis/%d/communes';

    public function __construct(
        private HttpClientInterface $httpClient,
        private TerritoryRepository $territoryRepository,
        private CommuneRepository $communeRepository,
        private EpciRepository $epciRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $response = $this->httpClient->request('GET', self::API_EPCI_ALL_URL);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $io->error('API failed');

            return Command::FAILURE;
        }

        $epciList = json_decode($response->getContent(), true);
        $progressBar = new ProgressBar($output, \count($epciList));
        $progressBar->start();
        foreach ($epciList as $epciItem) {
            $epci = $this->epciRepository->findOneBy(['nom' => $epciNom = $epciItem['nom']]);
            if (!$epci) {
                $epci = (new Epci())
                    ->setNom($epciNom)
                    ->setSlug($this->slugger->slug($epciNom));
            }
            $response = $this->httpClient->request(
                'GET',
                $epciCommunesUrl = sprintf(self::API_EPCI_COMMUNE_URL, $epciItem['code'])
            );

            if (Response::HTTP_OK === $response->getStatusCode()) {
                $communeList = json_decode($response->getContent(), true);
                foreach ($communeList as $communeItem) {
                    $communes = $this->communeRepository->findByCodesPostaux($communeItem['codesPostaux']);
                    foreach ($communes as $commune) {
                        $epci->addCommune($commune);
                    }
                }
                $this->entityManager->flush();
            } else {
                $io->error(sprintf('API failed for: %s', $epciCommunesUrl));
            }
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
                '%d communes do not belong to EPCI, the code postal might be obsolete.',
                $nbCommunesWithoutECPI
            ));
        }

        return Command::SUCCESS;
    }
}
