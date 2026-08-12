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

    /**
     * @var array<string>
     *                    AP : appartement
     *                    MA : maison
     *                    MP : maison partagée (maison ou appartement dont l’emprise est à cheval sur 2 communes)
     *                    ME : maison exceptionnelle d'habitation
     */
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
        #[Autowire(env: 'RIAL_ENABLE')]
        private readonly string $rialEnable,
    ) {
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): ?string
    {
        if ($this->rialEnable) {
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
                $context = [];
                if (Response::HTTP_UNAUTHORIZED === $response->getStatusCode()) {
                    try {
                        $ipResponse = $this->httpClient->request('GET', 'https://api.ipify.org');
                        $context['outbound_ip'] = $ipResponse->getContent();
                    } catch (\Throwable) {
                        $context['outbound_ip'] = 'unknown';
                    }
                }
                $this->logger->warning(\sprintf('Rial API access token failed (status %s)', $response->getStatusCode()), $context);
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return null;
    }

    public function getSingleInvariantByBanId(string $banId): ?string
    {
        if ($this->rialEnable) {
            $listLocaux = $this->searchLocauxByBanId($banId);
            if (empty($listLocaux)) {
                return null;
            }

            // Multiple results: check if only one "habitation principale"
            $result = null;
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

        return null;
    }

    /**
     * Recherche de locaux par adresse codifiée
     * GET /locaux/adresseTopographique
     * Obtenir un ou plusieurs identifiants de locaux à partir de leur adresse topographique codifiée.
     *
     * In sandbox env: Only available cities are Aulnay sous bois, Ajaccio and Pointe à Pitre.
     *
     * @return ?array<mixed>
     */
    public function searchLocauxByBanId(string $banId): ?array
    {
        if ($this->rialEnable) {
            $accessToken = $this->getAccessToken();
            if (empty($accessToken)) {
                return null;
            }

            $params = RialSearchLocauxParams::getFromBanId($banId);
            if (empty($params)) {
                return null;
            }

            $queryParams = '?'.http_build_query($params);
            $url = $this->urlDgfip.self::URI_LOCAUX_BY_ADRESSE.$queryParams;
            $rialHeaders = RialHeaders::getSearchLocauxHeaders($accessToken);
            $headers = $rialHeaders['headers'];
            $correlationId = $rialHeaders['correlation_id'];

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => $headers,
                ]);

                if (Response::HTTP_OK === $response->getStatusCode()) {
                    $responseArray = $response->toArray();

                    return $responseArray['listeIdentifiantsFiscaux'];
                }
                $context = [
                    'correlation_id' => $correlationId,
                    'ban_id' => $banId,
                    'params' => $params,
                ];
                if (Response::HTTP_UNAUTHORIZED === $response->getStatusCode()
                    || Response::HTTP_BAD_REQUEST === $response->getStatusCode()
                ) {
                    try {
                        $ipResponse = $this->httpClient->request('GET', 'https://api.ipify.org');
                        $context['outbound_ip'] = $ipResponse->getContent();
                    } catch (\Throwable) {
                        $context['outbound_ip'] = 'unknown';
                    }
                    $context['response'] = $response->getContent(false);
                }
                $this->logger->warning(
                    \sprintf('Rial API search by BAN id failed for: %s (status %s)',
                        $url,
                        $response->getStatusCode()),
                    $context
                );
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return null;
    }

    /**
     * Restitution de locaux par identifiants fiscaux
     * GET /locaux/{identifiantFiscal}
     * Obtenir les descriptifs détaillés de locaux à partir de leurs identifiants fiscaux respectifs.
     *
     * @return ?array<mixed>
     */
    public function searchLocalByIdFiscal(string $identifiantFiscal): ?array
    {
        if ($this->rialEnable) {
            $accessToken = $this->getAccessToken();
            if (empty($accessToken)) {
                return null;
            }

            $url = $this->urlDgfip.self::URI_LOCAL_BY_ID;
            $url = sprintf($url, $identifiantFiscal);
            $rialHeaders = RialHeaders::getSearchLocauxHeaders($accessToken);
            $headers = $rialHeaders['headers'];
            $correlationId = $rialHeaders['correlation_id'];

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => $headers,
                ]);

                if (Response::HTTP_OK === $response->getStatusCode()) {
                    $responseArray = $response->toArray();

                    return $responseArray[0];
                }
                $context = ['correlation_id' => $correlationId];
                if (Response::HTTP_UNAUTHORIZED === $response->getStatusCode()
                    || Response::HTTP_BAD_REQUEST === $response->getStatusCode()
                ) {
                    try {
                        $ipResponse = $this->httpClient->request('GET', 'https://api.ipify.org');
                        $context['outbound_ip'] = $ipResponse->getContent();
                    } catch (\Throwable) {
                        $context['outbound_ip'] = 'unknown';
                    }
                    $context['response'] = $response->getContent(false);
                }
                $this->logger->warning(\sprintf(
                    'Rial API search by invariant failed for: %s (status %s)',
                    $url,
                    $response->getStatusCode()),
                    $context
                );
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return null;
    }
}
