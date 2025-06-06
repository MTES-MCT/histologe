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
    private const string URI_LOCAL_BY_ID = '/rial/v1/locaux/%s';
    private const string URI_LOCAUX_BY_ADRESSE = '/rial/v1/locaux/adressetopographique';

    private const array CODES_NATURES_ACCEPTED = ['AP', 'MA', 'MP', 'ME'];

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

    public function getSingleInvariantByBanId(string $banId): ?string
    {
        $listLocaux = $this->searchLocauxByBanId($banId);
        if (empty($listLocaux)) {
            return null;

        // Single result: direct return
        } elseif (1 === \count($listLocaux)) {
            return $listLocaux[0];
        }
        $result = null;

        // Multiple results: check if only one "habitation principale"
        foreach ($listLocaux as $localId) {
            $infoLocal = $this->searchLocalByIdFiscal($localId);
            if (!empty($infoLocal) && in_array($infoLocal['descriptifGeneralLocal']['codeNatureLocal'], self::CODES_NATURES_ACCEPTED)) {
                if (empty($result)) {
                    $result = $localId;
                } else {
                    $result = null;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Only available cities in sandbox are Aulnay sous bois, Ajaccio and Pointe à Pitre.
     */
    public function searchLocauxByBanId(string $banId): ?array
    {
        $accessToken = $this->getAccesssToken();
        if (empty($accessToken)) {
            return null;
        }

        $params = RialSearchLocauxParams::getFromBanId($banId);
        if (empty($params)) {
            return null;
        }

        $queryParams = '?'.http_build_query($params);
        $url = $this->urlDgfip.self::URI_LOCAUX_BY_ADRESSE.$queryParams;
        $headers = RialHeaders::getSearchLocauxHeaders($accessToken);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
            ]);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                $responseArray = $response->toArray();

                return $responseArray['listeIdentifiantsFiscaux'];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function searchLocalByIdFiscal(string $identifiantFiscal): ?array
    {
        $accessToken = $this->getAccesssToken();
        if (empty($accessToken)) {
            return null;
        }

        $url = $this->urlDgfip.self::URI_LOCAL_BY_ID;
        $url = sprintf($url, $identifiantFiscal);
        $headers = RialHeaders::getSearchLocauxHeaders($accessToken);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
            ]);

            if (Response::HTTP_OK === $response->getStatusCode()) {
                $responseArray = $response->toArray();

                return $responseArray[0];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }
}
