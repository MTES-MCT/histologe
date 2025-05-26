<?php

namespace App\Service\Gouv\Rial;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RialService
{
    private const string URI_GENERATE_TOKEN = '/token';
    private const string URI_LOCAUX_BY_ADRESSE = '/locaux/adresseTopographique';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private string $accessToken,
        #[Autowire(env: 'RIAL_URL')]
        private readonly string $rialUrl,
        #[Autowire(env: 'RIAL_KEY')]
        private readonly string $rialKey,
        #[Autowire(env: 'RIAL_SECRET')]
        private readonly string $rialSecret,
    ) {
    }

    public function generateAccessToken(): string
    {
        $response = $this->httpClient->request('POST', $this->rialUrl . self::URI_GENERATE_TOKEN, [
            'headers' => ['Authorization' => 'Basic '.$this->rialKey.':'.$this->rialSecret],
        ]);

        $this->accessToken = $response->getContent();
        return $this->accessToken;
    }

    public function searchLocauxByAdresse(
        string $codeDepartementInsee,
        string $codeCommuneInsee,
        string $codeVoieTopo,
        string $numeroVoirie,
    ): ?array {
        $params = [
            'codeDepartementInsee' => $codeDepartementInsee,
            'codeCommuneInsee' => $codeCommuneInsee,
            'codeVoieTopo' => $codeVoieTopo,
            'numeroVoirie' => $numeroVoirie,
        ];
        $queryParams = '?'.http_build_query($params);
        $url = $this->rialUrl . self::URI_LOCAUX_BY_ADRESSE . $queryParams;
        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['Authorization' => 'Bearer '.$this->accessToken],
        ]);

        if (Response::HTTP_OK === $response->getStatusCode()) {
            return $response->toArray();
        }

        return null;
    }
}
