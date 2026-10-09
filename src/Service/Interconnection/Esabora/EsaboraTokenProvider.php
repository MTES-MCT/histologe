<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Enum\InterconnectionAuthType;
use App\Entity\Partner;
use App\Service\Interconnection\Esabora\Exception\EsaboraTokenException;
use App\Service\Interconnection\Esabora\Response\OAuth2TokenResponse;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EsaboraTokenProvider
{
    public const int DEFAULT_EXPIRATION_SECONDS = 900;
    public const int EXPIRATION_MARGIN_SECONDS = 60;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getUrl(Partner $partner): string
    {
        return $partner->getEsaboraUrl();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getToken(Partner $partner): string
    {
        return match ($partner->getAuthenticationType()) {
            InterconnectionAuthType::STATIC_TOKEN => $this->getStaticToken($partner),
            InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS => $this->getOAuth2Token($partner),
            null => throw new EsaboraTokenException(sprintf('Le mode d\'authentification Esabora n\'est pas configuré pour le partenaire "%s".', $partner->getNom())),
        };
    }

    private function getStaticToken(Partner $partner): string
    {
        $token = $partner->getEsaboraToken();
        if (empty($token)) {
            throw new EsaboraTokenException(sprintf('Aucun token Esabora n\'est configuré pour le partenaire "%s".', $partner->getNom()));
        }

        return $token;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getOAuth2Token(Partner $partner): string
    {
        $tokenUrl = $partner->getOauth2TokenUrl();
        $clientId = $partner->getOauth2ClientId();
        $clientSecret = $partner->getOauth2ClientSecret();

        if (empty($tokenUrl) || empty($clientId) || empty($clientSecret)) {
            throw new EsaboraTokenException(sprintf('La configuration OAuth2 est incomplète pour le partenaire "%s".', $partner->getNom()));
        }

        $partnerId = $partner->getId() ?? $partner->getUuid() ?? 'unknown';
        $cacheKey = sprintf('esabora.oauth2.access_token.%s', $partnerId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($partner, $tokenUrl, $clientId, $clientSecret): string {
            $oAuth2Token = $this->requestOAuth2Token($partner, $tokenUrl, $clientId, $clientSecret);
            $expiresIn = isset($oAuth2Token->expiresIn) && is_numeric($oAuth2Token->expiresIn)
                ? (int) $oAuth2Token->expiresIn
                : self::DEFAULT_EXPIRATION_SECONDS;

            $ttl = max(1, $expiresIn - self::EXPIRATION_MARGIN_SECONDS);
            $item->expiresAfter($ttl);

            return $oAuth2Token->accessToken;
        });
    }

    private function requestOAuth2Token(
        Partner $partner,
        string $tokenUrl,
        string $clientId,
        string $clientSecret,
    ): OAuth2TokenResponse {
        $body = [
            'grant_type' => 'client_credentials',
            'scope' => $partner->getOauth2Scope(),
        ];

        try {
            $response = $this->client->request('POST', $tokenUrl, [
                'auth_basic' => [$clientId, $clientSecret],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error(
                    sprintf('[Esabora OAuth2] Erreur HTTP %d lors de la récupération du token pour le partenaire ID %s.', $statusCode, $partner->getId())
                );
                throw new EsaboraTokenException(sprintf('Erreur HTTP %d lors de la récupération du token OAuth2 pour le partenaire "%s".', $statusCode, $partner->getNom()));
            }

            return new OAuth2TokenResponse($response->toArray(throw: false));
        } catch (EsaboraTokenException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('[Esabora OAuth2] Échec de la récupération du token pour le partenaire ID %s : %s', $partner->getId(), $exception->getMessage())
            );
            throw new EsaboraTokenException(sprintf('Échec de la récupération du token OAuth2 pour le partenaire "%s".', $partner->getNom()), previous: $exception);
        }
    }
}
