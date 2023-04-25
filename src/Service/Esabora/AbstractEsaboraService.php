<?php

namespace App\Service\Esabora;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AbstractEsaboraService
{
    public const TYPE_SERVICE = 'esabora';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';
    public const ACTION_PUSH_DOSSIER_PERSONNE = 'push_dossier_personne';
    public const ACTION_PUSH_DOSSIER_ADRESSE = 'push_dossier_adresse';
    public const ACTION_SYNC_DOSSIER = 'sync_dossier';
    public const TASK_INSERT = 'doTreatment';
    public const TASK_SEARCH = 'doSearch';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function request(string $url, string $token, string $task, array $payload): ResponseInterface|JsonResponse
    {
        try {
            return $this->client->request('POST', $url.'/modbdd/?task='.$task, [
                    'headers' => [
                        'Authorization: Bearer '.$token,
                        'Content-Type: application/json',
                    ],
                    'body' => json_encode($payload),
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return (new JsonResponse([
            'message' => $exception->getMessage(),
        ]))->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
