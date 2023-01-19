<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Messenger\Message\DossierMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraService
{
    public const ESABORA_WAIT = 'A traiter';
    public const ESABORA_ACCEPTED = 'Importé';
    public const ESABORA_REFUSED = 'Non importé';
    public const ESABORA_CLOSED = 'terminé';

    public const TYPE_SERVICE = 'esabora';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';
    public const ACTION_SYNC_DOSSIER = 'sync_dossier';

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    public function pushDossier(DossierMessage $dossierMessage): ResponseInterface|JsonResponse
    {
        $url = $dossierMessage->getUrl();
        $token = $dossierMessage->getToken();
        $payload = [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList' => $dossierMessage->preparePayload(),
        ];

        try {
            return $this->client->request('POST', $url.'/modbdd/?task=doTreatment', [
                    'headers' => [
                        'Authorization: Bearer '.$token,
                        'Content-Type: application/json',
                    ],
                    'body' => json_encode($payload, \JSON_THROW_ON_ERROR),
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return (new JsonResponse([
            'message' => $exception->getMessage(),
        ]))->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }

    public function getStateDossier(Affectation $affectation): DossierResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $payload = [
            'searchName' => 'WS_ETAT_DOSSIER_SAS',
            'criterionList' => [
                [
                    'criterionName' => 'SAS_Référence',
                    'criterionValueList' => [
                        $affectation->getSignalement()->getUuid(),
                    ],
                ],
            ],
        ];

        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        try {
            $response = $this->client->request('POST', $url.'/mult/?task=doSearch', [
                    'headers' => [
                        'Authorization: Bearer '.$token,
                        'Content-Type: application/json',
                    ],
                    'body' => json_encode($payload, \JSON_THROW_ON_ERROR),
                ]
            );
            $statusCode = $response->getStatusCode();

            return new DossierResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR !== $statusCode
                    ? $response->toArray()
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return new DossierResponse(['message' => $exception->getMessage(), 'status_code' => $statusCode], $statusCode);
    }
}
