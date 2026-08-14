<?php

namespace App\Service\Gouv\Topo;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TopoService
{
    private const string API_URL = 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/topo-fichier-des-entites-topographiques/records';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *     code_dep: string,
     *     code_commune: string,
     *     code_voie: string,
     *     nature_de_voie: string,
     *     libelle: string
     * }[]
     */
    public function searchVoies(
        string $codeDepartement,
        string $codeCommune,
        string $libelle,
    ): array {
        $this->logger->info(sprintf('TOPO DGFiP search request: dep=%s, commune=%s, libelle=%s', $codeDepartement, $codeCommune, $libelle));
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'select' => 'code_dep,code_commune,code_voie,nature_de_voie,libelle',
                    'where' => sprintf(
                        'code_dep="%s" AND code_commune="%s" AND search(libelle,"%s")',
                        $codeDepartement,
                        $codeCommune,
                        $libelle,
                    ),
                    'limit' => 20,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('TOPO DGFiP API response status: '.$statusCode);

            if (200 !== $statusCode) {
                $this->logger->error('TOPO DGFiP API error: '.$statusCode.' '.$response->getContent(false));

                return [];
            }

            $data = $response->toArray();
            $results = $data['results'] ?? [];
            $this->logger->info(sprintf('TOPO DGFiP search results: %d found', \count($results)));

            return $results;
        } catch (\Throwable $e) {
            $this->logger->error('TOPO DGFiP API exception: '.$e->getMessage());

            return [];
        }
    }
}
