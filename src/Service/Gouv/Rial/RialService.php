<?php

namespace App\Service\Gouv\Rial;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RialService
{
    private const string URI_GENERATE_TOKEN = '/token';
    //private const string URI_LOCAUX_BY_ADRESSE = '/rial/v1/locaux/adresseTopographique';
    private const string URI_LOCAUX_BY_ADRESSE = '/locaux/adressetopographique';

    private string $accessToken;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'DGFIP_URL')]
        private readonly string $dgfipUrl,
        #[Autowire(env: 'RIAL_URL')]
        private readonly string $rialUrl,
        #[Autowire(env: 'RIAL_KEY')]
        private readonly string $rialKey,
        #[Autowire(env: 'RIAL_SECRET')]
        private readonly string $rialSecret,
        #[Autowire(env: 'TEST_TOKEN')]
        private readonly string $testToken,
        #[Autowire(env: 'CORRELATION_ID')]
        private readonly string $correlationId,
    ) {
    }

    public function generateAccessToken(): ?string
    {
        $params = [
            'grant_type' => 'client_credentials'
        ];
        $queryParams = '?'.http_build_query($params);
        $keyEncoded = base64_encode($this->rialKey.':'.$this->rialSecret);
        $url = $this->dgfipUrl . self::URI_GENERATE_TOKEN . $queryParams;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Authorization' => 'Basic '.$keyEncoded],
                'timeout' => 5,
            ]);

            $this->accessToken = $response->getContent();
            return $this->accessToken;

        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            dd($exception->getMessage());
        }

        return null;
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
        //$token = $this->accessToken;
        $token = $this->testToken;
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'X-Correlation-ID' => $this->correlationId
            ],
            'timeout' => 3,
        ]);
        dd($response);

        if (Response::HTTP_OK === $response->getStatusCode()) {
            return $response->toArray();
        }

        return null;
    }
}
