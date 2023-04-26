<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Service\Esabora\Response\DossierResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AbstractEsaboraService implements EsaboraServiceInterface
{
    public const TYPE_SERVICE = 'esabora';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';
    public const ACTION_PUSH_DOSSIER_PERSONNE = 'push_dossier_personne';
    public const ACTION_PUSH_DOSSIER_ADRESSE = 'push_dossier_adresse';
    public const ACTION_SYNC_DOSSIER = 'sync_dossier';
    public const TASK_INSERT = 'doTreatment';
    public const TASK_SEARCH = 'doSearch';
    public const SIGNALEMENT_ORIGINE = 'interfaçage';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function request(string $url, string $token, string $task, array $payload): ResponseInterface|JsonResponse
    {
        try {
            $this->logger->info(json_encode($payload));

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

    public function getStateDossier(Affectation $affectation): ?DossierResponseInterface
    {
        return null;
    }
}
