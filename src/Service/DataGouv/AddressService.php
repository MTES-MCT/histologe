<?php

namespace App\Service\DataGouv;

use App\Service\DataGouv\Response\Address;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AddressService
{
    private const API_URL = 'https://api-adresse.data.gouv.fr/search/?q=';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function searchAddress(string $query): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL.urlencode($query));

            if (200 === $response->getStatusCode()) {
                return $response->toArray();
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
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

    public function getAddress(string $address): Address
    {
        return new Address($this->searchAddress($address));
    }
}
