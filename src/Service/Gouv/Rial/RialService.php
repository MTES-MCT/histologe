<?php

namespace App\Service\Gouv\Rial;

use App\Service\Gouv\Rial\Response\InvariantsFiscaux;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RialService
{
    // TODO : se connecter à l'API
    // https://ip-gateway/rial/v1/locaux/adresseTopographique?codeDepartementInsee=93&codeCommuneInsee=200&codeVoieTopo=1234&numeroVoirie=0011&etage=04
    private const string API_URL = 'https://ip-gateway/rial/v1/locaux/adresseTopographique?';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function searchInvariant(string $interopKey): ?array
    {
        $interopArray = explode('_', $interopKey);
        try {
            $url = self::API_URL.
                'codeDepartementInsee='.substr($interopArray[0], 0, 2). // TODO : var avec les DOM et la Corse : https://www.data.gouv.fr/fr/dataservices/rial-repertoire-inter-administratif-des-locaux/
                '&codeCommuneInsee='.substr($interopArray[0], 2, 3).
                '&codeVoieTopo='.$interopArray[1].
                '&numeroVoirie='.$interopArray[2];
            $response = $this->httpClient->request('GET', $url);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                return $response->toArray();
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function getInvariantsFiscaux(string $interopKey): ?InvariantsFiscaux
    {
        return new InvariantsFiscaux($this->searchInvariant($interopKey));
    }
}
