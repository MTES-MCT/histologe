<?php

namespace App\Service\Interconnection;

use App\Manager\JobEventManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class JobEventHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly JobEventManager $jobEventManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $jobEventMetaData = $options['extra']['job_event_metadata'] ?? null;
        if (null === $jobEventMetaData) {
            throw new \InvalidArgumentException(<<<'ERROR'
                To use JobEventHttpClient, the "job_event_metadata" option must be an instance of JobEventMetadata,
                passed as an extra option in the HTTP client configuration:

                $options['extra']['job_event_metadata'] = new JobEventMetaData();
                $response = $this->request($url, $token, $payload, $options);

                Otherwise please ensure that the service is declared with the default http_client argument in services.yaml

                services:
                    Your\Service\Class:
                        arguments:
                            - '@http_client.default'
                ERROR);
        }
        $this->logger->info('Starting HTTP request', [
            'method' => $method,
            'url' => $url,
            'options' => $options,
        ]);

        $response = $this->httpClient->request($method, $url, $options);

        $this->logger->info('HTTP request completed', [
            'status_code' => $response->getStatusCode(),
            'response_content' => $response->getContent(),
        ]);

        /** @var JobEventMetaData $jobEventMetaData */
        $payload = [];
        if (null !== $jobEventMetaData->getPayload()) {
            $payload = $this->filterPayload($jobEventMetaData->getPayload());
        }
        $responseContent = $this->filterResponse($response->getContent());

        $this->jobEventManager->createJobEvent(
            service: $jobEventMetaData->getService(),
            action: $jobEventMetaData->getAction(),
            message: json_encode($payload),
            response: json_encode($responseContent),
            status: 200 === $response->getStatusCode() ? 'success' : 'failure',
            codeStatus: $response->getStatusCode(),
            signalementId: $jobEventMetaData->getSignalementId(),
            partnerId: $jobEventMetaData->getPartnerId(),
            partnerType: $jobEventMetaData->getPartnerType(),
        );

        return $response;
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->httpClient->stream($responses, $timeout);
    }

    private function filterPayload($payload): array
    {
        if (!isset($payload['fieldList'])) {
            return $payload;
        }

        $payload['fieldList'] = array_filter($payload['fieldList'], function ($field) {
            return isset($field['fieldName']) && 'PJ_Documents' !== $field['fieldName'];
        });

        return array_values($payload);
    }

    private function filterResponse($response): array
    {
        $responseDecoded = json_decode($response, true);
        if (isset($responseDecoded['rowList'][0]['documentZipContent'])) {
            unset($responseDecoded['rowList'][0]['documentZipContent']);
        }

        return $responseDecoded;
    }
}
