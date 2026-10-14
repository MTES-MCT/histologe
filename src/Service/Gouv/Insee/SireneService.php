<?php

namespace App\Service\Gouv\Insee;

use App\Service\Gouv\Insee\Exception\SireneRateLimitExceededException;
use App\Service\Gouv\Insee\Response\SiretInformation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SireneService
{
    private const string SIRENE_API_URL = 'https://api.insee.fr/api-sirene/3.11';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'SIRENE_API_KEY')] private readonly string $sireneApiKey,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getSiret(string $siret): SiretInformation
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('%s/siret/%s', self::SIRENE_API_URL, $siret),
            [
                'headers' => [
                    'X-INSEE-Api-Key-Integration' => $this->sireneApiKey,
                ],
            ],
        );

        try {
            $data = $response->toArray();
        } catch (ClientExceptionInterface $exception) {
            if (Response::HTTP_TOO_MANY_REQUESTS === $response->getStatusCode()) {
                throw new SireneRateLimitExceededException(message: 'Le quota de requêtes de l\'API Sirene est temporairement dépassé.', previous: $exception);
            }

            throw $exception;
        }

        $codeCommune = $data['etablissement']['adresseEtablissement']['codeCommuneEtablissement']
            ?? null;

        if (!is_string($codeCommune) || '' === $codeCommune) {
            throw new \UnexpectedValueException(sprintf('Aucun code commune trouvé pour le SIRET "%s".', $siret));
        }

        return new SiretInformation(
            siret: $siret,
            codeCommune: $codeCommune,
        );
    }
}
