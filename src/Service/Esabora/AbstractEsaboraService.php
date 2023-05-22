<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Service\Esabora\Response\DossierResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractEsaboraService implements EsaboraServiceInterface
{
    public const TYPE_SERVICE = 'esabora';
    public const SISH_VISITES_DOSSIER_SAS = 'SISH_VISITES_DOSSIER_SAS';
    public const SISH_ARRETES_DOSSIER_SAS = 'SISH_ARRETES_DOSSIER_SAS';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';
    public const ACTION_PUSH_DOSSIER_PERSONNE = 'push_dossier_personne';
    public const ACTION_PUSH_DOSSIER_ADRESSE = 'push_dossier_adresse';
    public const ACTION_SYNC_DOSSIER = 'sync_dossier';
    public const ACTION_SYNC_DOSSIER_VISITE = 'sync_dossier_visite';
    public const ACTION_SYNC_DOSSIER_ARRETE = 'sync_dossier_arrete';
    public const TASK_INSERT = 'doTreatment';
    public const SIGNALEMENT_ORIGINE = 'interfaçage';
    public const FORMAT_DATE = 'd/m/Y';
    public const FORMAT_DATE_TIME = 'd/m/Y H:i';

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

    abstract public function getStateDossier(Affectation $affectation): DossierResponseInterface;

    public function prepareInterventionPayload(Affectation $affectation, string $serviceName): array
    {
        return [
            'searchName' => $serviceName,
            'criterionList' => [
                [
                    'criterionName' => 'Reference_Dossier',
                    'criterionValueList' => [
                        $affectation->getSignalement()->getUuid(),
                    ],
                ],
                [
                    'criterionName' => 'Logiciel_Provenance',
                    'criterionValueList' => [
                        'H',
                    ],
                ],
            ],
        ];
    }
}
