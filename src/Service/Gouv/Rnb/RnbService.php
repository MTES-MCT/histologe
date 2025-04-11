<?php

namespace App\Service\Gouv\Rnb;

use App\Service\Gouv\Rnb\Response\RnbBuilding;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RnbService
{
    private const string API_URL = 'https://rnb-api.beta.gouv.fr/api/alpha/buildings/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function searchBuildings(?string $rnbId = null, array $queryParams = []): ?array
    {
        try {
            $url = self::API_URL;
            if (null !== $rnbId) {
                $url .= $rnbId.'/';
            }
            if (!empty($queryParams)) {
                $url .= '?'.http_build_query($queryParams);
            }
            $response = $this->httpClient->request('GET', $url);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                return $response->toArray();
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function getBuildings(string $cleInteropBan): array
    {
        $buildings = [];
        $results = $this->searchBuildings(queryParams: ['cle_interop_ban' => $cleInteropBan]);
        if (null !== $results && !empty($results['results'])) {
            foreach ($results['results'] as $item) {
                $buildings[] = new RnbBuilding($item);
            }
        }

        return $buildings;
    }

    public function getBuilding(string $rnbId): ?RnbBuilding
    {
        $result = $this->searchBuildings(rnbId: $rnbId);
        if (!empty($result)) {
            return new RnbBuilding($result);
        }

        return null;
    }
}
