<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\Response\DossierCollectionResponseInterface;
use App\Service\Interconnection\Esabora\Response\DossierResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractEsaboraService implements EsaboraServiceInterface
{
    public const string TYPE_SERVICE = 'esabora';
    public const string SISH_VISITES_DOSSIER_SAS = 'SISH_VISITES_DOSSIER_SAS';
    public const string SISH_ARRETES_DOSSIER_SAS = 'SISH_ARRETES_DOSSIER_SAS';
    public const string ACTION_PUSH_DOSSIER = 'push_dossier';
    public const string ACTION_PUSH_DOSSIER_PERSONNE = 'push_dossier_personne';
    public const string ACTION_PUSH_DOSSIER_ADRESSE = 'push_dossier_adresse';
    public const string ACTION_SYNC_DOSSIER = 'sync_dossier';
    public const string ACTION_SYNC_DOSSIER_VISITE = 'sync_dossier_visite';
    public const string ACTION_SYNC_DOSSIER_ARRETE = 'sync_dossier_arrete';
    public const string TASK_INSERT_PATH = '/modbdd/?task=doTreatment';
    public const string TASK_SEARCH_PATH = '/mult/?task=doSearch';
    public const string TASK_GET_DOCUMENTS = '/mult/?task=getDocuments';
    public const string SIGNALEMENT_ORIGINE = 'interfaçage';
    public const string FORMAT_DATE = 'd/m/Y';
    public const string FORMAT_DATE_TIME = 'd/m/Y H:i';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<mixed> $payload
     * @param array<mixed> $requestOptions
     */
    protected function request(
        string $url,
        string $token,
        array $payload,
        array $requestOptions = [],
    ): ResponseInterface|JsonResponse {
        try {
            $taskPath = $this->getTaskPath($payload);

            $options = [
                'headers' => [
                    'Authorization: Bearer '.$token,
                    'Content-Type: application/json',
                ],
                'body' => json_encode($payload),
            ];

            $options = [...$options, ...$requestOptions];

            return $this->client->request('POST', $url.$taskPath, $options);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return (new JsonResponse([
            'message' => $exception->getMessage(),
        ]))->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }

    abstract public function getStateDossier(Affectation $affectation, string $uuidSignalement): DossierResponseInterface;

    /**
     * @return array<mixed>
     */
    public function prepareInterventionPayload(string $uuidSignalement, string $serviceName): array
    {
        return [
            'searchName' => $serviceName,
            'criterionList' => [
                [
                    'criterionName' => 'Reference_Dossier',
                    'criterionValueList' => [
                        $uuidSignalement,
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

    public static function hasSuccess(
        DossierResponseInterface|DossierCollectionResponseInterface $dossierResponse,
    ): bool {
        return Response::HTTP_OK === $dossierResponse->getStatusCode()
            && null !== $dossierResponse->getSasEtat()
            && null === $dossierResponse->getErrorReason();
    }

    /**
     * @param array<mixed> $payload
     */
    protected function getTaskPath(array $payload): ?string
    {
        if (empty($payload)) {
            return null;
        }
        if (\array_key_exists('searchName', $payload)) {
            return self::TASK_SEARCH_PATH;
        }

        return self::TASK_INSERT_PATH;
    }
}
