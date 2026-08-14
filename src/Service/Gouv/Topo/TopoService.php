<?php

namespace App\Service\Gouv\Topo;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
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

            if (Response::HTTP_OK !== $statusCode) {
                $this->logger->error(sprintf('TOPO DGFiP API error: %s %s', $statusCode, $response->getContent(false)));

                return [];
            }

            $data = $response->toArray();

            return $data['results'] ?? [];
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf('TOPO DGFiP API exception: %s', $exception->getMessage()));

            return [];
        }
    }
}
