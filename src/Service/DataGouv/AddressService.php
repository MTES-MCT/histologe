<?php

namespace App\Service\DataGouv;

use App\Service\DataGouv\Response\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AddressService
{
    private const string API_URL = 'https://api-adresse.data.gouv.fr/search/?q=';
    private const string API_PARAM_LIMIT = '&limit=1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function searchAddress(string $query): ?array
    {
        try {
            $url = self::API_URL.urlencode($query).self::API_PARAM_LIMIT;
            $response = $this->httpClient->request('GET', $url);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                return $response->toArray();
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function getAddress(string $address): Address
    {
        return new Address($this->searchAddress($address));
    }
}
