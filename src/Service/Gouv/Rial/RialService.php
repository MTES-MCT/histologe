<?php

namespace App\Service\Gouv\Rial;

use App\Service\Gouv\Rial\Request\RialHeaders;
use App\Service\Gouv\Rial\Request\RialSearchLocauxParams;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RialService
{
    private const string URI_GENERATE_TOKEN = '/token';
    private const string URI_LOCAUX_BY_ADRESSE = '/rial/v1/locaux/adressetopographique';

    private string $accessToken;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'URL_DGFIP')]
        private readonly string $urlDgfip,
        #[Autowire(env: 'RIAL_KEY')]
        private readonly string $rialKey,
        #[Autowire(env: 'RIAL_SECRET')]
        private readonly string $rialSecret,
    ) {
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccesssToken(): ?string
    {
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        $url = $this->urlDgfip.self::URI_GENERATE_TOKEN;
        $headers = RialHeaders::getGenerateTokenHeaders($this->rialKey, $this->rialSecret);
        $params = [
            'grant_type' => 'client_credentials',
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'body' => http_build_query($params),
            ]);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                $this->accessToken = $response->toArray()['access_token'];

                return $this->accessToken;
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    /**
     * Only available cities in sandbox are Aulnay sous bois, Ajaccio and Pointe à Pitre.
     */
    public function searchLocauxByAdresse(string $banId): ?array
    {
        $accessToken = $this->getAccesssToken();
        if (empty($accessToken)) {
            return null;
        }

        $params = RialSearchLocauxParams::getFromBanId($banId);
        $queryParams = '?'.http_build_query($params);
        $url = $this->urlDgfip.self::URI_LOCAUX_BY_ADRESSE.$queryParams;
        $headers = RialHeaders::getSearchLocauxHeaders($accessToken);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
            ]);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                return $response->toArray();
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }
}
