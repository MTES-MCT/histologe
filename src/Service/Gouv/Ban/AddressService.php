<?php

namespace App\Service\Gouv\Ban;

use App\Service\Gouv\Ban\Response\Address;
use App\Service\Gouv\Ban\Response\Poi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AddressService
{
    private const string API_URL = 'https://data.geopf.fr/geocodage/search/?q=';
    private const string API_PARAM_LIMIT = '&limit=1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
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

    public function getMunicipalityByCityCode(string $cityName, string $cityCode): ?Poi
    {
        try {
            $url = self::API_URL.urlencode($cityName).'&citycode='.urlencode($cityCode).'&index=poi&category=commune&autocomplete=0'.self::API_PARAM_LIMIT;
            $response = $this->httpClient->request('GET', $url);
            if (Response::HTTP_OK === $response->getStatusCode()) {
                return new Poi($response->toArray());
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
