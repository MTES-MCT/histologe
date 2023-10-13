<?php

namespace App\Service\DataGouv;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AddressService
{
    private $apiUrl = 'https://api-adresse.data.gouv.fr/search/?q=';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function searchAddress(string $query): ?array
    {
        $response = $this->httpClient->request('GET', $this->apiUrl.urlencode($query));

        if (200 === $response->getStatusCode()) {
            return $response->toArray();
        }

        return null;
    }

    public function getCodeInsee(string $address): ?string
    {
        $response = $this->searchAddress($address);

        if (null !== $response && !empty($response['features'])) {
            $codeInsee = $response['features'][0]['properties']['citycode'];

            return $codeInsee;
        }

        return null;
    }
}
