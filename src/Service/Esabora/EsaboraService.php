<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Messenger\Message\DossierMessage;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraService
{
    public const ESABORA_WAIT = 'A traiter';
    public const ESABORA_ACCEPTED = 'Importé';
    public const ESABORA_IN_PROGRESS = 'en cours';
    public const ESABORA_CLOSED = 'terminé';
    public const ESABORA_REFUSED = 'Non importé';

    public const TYPE_SERVICE = 'esabora';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';
    public const ACTION_SYNC_DOSSIER = 'sync_dossier';

    public function __construct(
        private HttpClientInterface $client,
        private UploadHandlerService $uploadHandlerService,
        private LoggerInterface $logger,
    ) {
    }

    public function pushDossier(DossierMessage $dossierMessage): ResponseInterface|JsonResponse
    {
        $url = $dossierMessage->getUrl();
        $token = $dossierMessage->getToken();
        $payload = [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList' => $this->preparePayloadPushDossier($dossierMessage),
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

    public function preparePayloadPushDossier(DossierMessage $dossierMessage, bool $encodeDocuments = true): array
    {
        $piecesJointes = [];
        if ($encodeDocuments) {
            $piecesJointes = array_map(function ($pieceJointe) {
                $filepath = $this->uploadHandlerService->getTmpFilepath($pieceJointe['documentContent']);
                $pieceJointe['documentContent'] = base64_encode(file_get_contents($filepath));

                return $pieceJointe;
            }, $dossierMessage->getPiecesJointes());
        }

        return [
            [
                'fieldName' => 'Référence_Histologe',
                'fieldValue' => $dossierMessage->getReference(),
            ],
            [
                'fieldName' => 'Usager_Nom',
                'fieldValue' => $dossierMessage->getNomUsager(),
            ],
            [
                'fieldName' => 'Usager_Prénom',
                'fieldValue' => $dossierMessage->getPrenomUsager(),
            ],
            [
                'fieldName' => 'Usager_Mail',
                'fieldValue' => $dossierMessage->getMailUsager(),
            ],
            [
                'fieldName' => 'Usager_Téléphone',
                'fieldValue' => $dossierMessage->getTelephoneUsager(),
            ],
            [
                'fieldName' => 'Usager_Numéro',
                'fieldValue' => '',
            ],
            [
                'fieldName' => 'Usager_Nom_Rue',
                'fieldValue' => $dossierMessage->getAdresseSignalement(),
            ],
            [
                'fieldName' => 'Usager_Adresse2',
                'fieldValue' => '',
            ],
            [
                'fieldName' => 'Usager_CodePostal',
                'fieldValue' => $dossierMessage->getCodepostaleSignalement(),
            ],
            [
                'fieldName' => 'Usager_Ville',
                'fieldValue' => $dossierMessage->getVilleSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Numéro',
                'fieldValue' => $dossierMessage->getNumeroAdresseSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Nom_Rue',
                'fieldValue' => $dossierMessage->getAdresseSignalement(),
            ],
            [
                'fieldName' => 'Adresse_CodePostal',
                'fieldValue' => $dossierMessage->getCodepostaleSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Ville',
                'fieldValue' => $dossierMessage->getVilleSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Etage',
                'fieldValue' => $dossierMessage->getEtageSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Porte',
                'fieldValue' => $dossierMessage->getNumeroAppartementSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Latitude',
                'fieldValue' => $dossierMessage->getLatitudeSignalement(),
            ],
            [
                'fieldName' => 'Adresse_Longitude',
                'fieldValue' => $dossierMessage->getLongitudeSignalement(),
            ],
            [
                'fieldName' => 'Dossier_Ouverture',
                'fieldValue' => $dossierMessage->getDateOuverture(),
            ],
            [
                'fieldName' => 'Dossier_Commentaire',
                'fieldValue' => $dossierMessage->getDossierCommentaire(),
            ],
            [
                'fieldName' => 'PJ_Observations',
                'fieldValue' => $dossierMessage->getPiecesJointesObservation(),
            ],
            [
                'fieldName' => 'PJ_Documents',
                'fieldDocumentUpdate' => 1,
                'fieldValue' => $piecesJointes,
            ],
        ];
    }
}
