<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Service\Interconnection\Esabora\Response\DossierEventFilesSCHSResponse;
use App\Service\Interconnection\Esabora\Response\DossierEventsSCHSCollectionResponse;
use App\Service\Interconnection\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierEventSCHS;
use App\Service\Interconnection\JobEventMetaData;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraSCHSService extends AbstractEsaboraService
{
    private const string ACTION_SYNC_EVENTS = 'sync_events';
    private const string ACTION_SYNC_EVENTFILES = 'sync_eventfiles';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly UploadHandlerService $uploadHandlerService,
    ) {
        parent::__construct($this->client, $this->logger);
    }

    public function pushDossier(DossierMessageSCHS $dossierMessage): ResponseInterface|JsonResponse
    {
        $url = $dossierMessage->getUrl();
        $token = $dossierMessage->getToken();
        $payload = [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList' => $this->preparePayloadPushDossier($dossierMessage),
        ];

        $options['extra']['job_event_metadata'] = new JobEventMetaData(
            service: self::TYPE_SERVICE,
            action: $dossierMessage->getAction(),
            payload: $payload,
            signalementId: $dossierMessage->getSignalementId(),
            partnerId: $dossierMessage->getPartnerId(),
            partnerType: $dossierMessage->getPartnerType(),
        );

        return $this->request($url, $token, $payload, $options);
    }

    public function getStateDossier(Affectation $affectation): DossierStateSCHSResponse
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

        $options['extra']['job_event_metadata'] = new JobEventMetaData(
            service: self::TYPE_SERVICE,
            action: self::ACTION_SYNC_DOSSIER,
            payload: $payload,
            signalementId: $affectation->getSignalement()->getId(),
            partnerId: $affectation->getPartner()->getId(),
            partnerType: $affectation->getPartner()->getType(),
        );

        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        try {
            $response = $this->request($url, $token, $payload, $options);
            $statusCode = $response->getStatusCode();

            return new DossierStateSCHSResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR !== $statusCode
                    ? $response->toArray(throw: false)
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return new DossierStateSCHSResponse(
            ['message' => $exception->getMessage(), 'status_code' => $statusCode],
            $statusCode
        );
    }

    public function getDossierEvents(Affectation $affectation): DossierEventsSCHSCollectionResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        $payload = [
            'searchName' => 'WS_EVT_DOSSIER_SAS',
            'criterionList' => [
                [
                    'criterionName' => 'SAS_Référence',
                    'criterionValueList' => [
                        $affectation->getSignalement()->getUuid(),
                    ],
                ],
            ],
        ];

        $options['extra']['job_event_metadata'] = new JobEventMetaData(
            service: self::TYPE_SERVICE,
            action: self::ACTION_SYNC_EVENTS,
            payload: $payload,
            signalementId: $affectation->getSignalement()->getId(),
            partnerId: $affectation->getPartner()->getId(),
            partnerType: $affectation->getPartner()->getType(),
        );

        try {
            $response = $this->request($url, $token, $payload, $options);

            $statusCode = $response->getStatusCode();

            return new DossierEventsSCHSCollectionResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR !== $statusCode
                    ? $response->toArray(throw: false)
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            return new DossierEventsSCHSCollectionResponse(
                ['message' => $exception->getMessage(), 'status_code' => $statusCode],
                $statusCode
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function getDossierEventFiles(
        Affectation $affectation,
        DossierEventSCHS $dossierEventSCHS
    ): DossierEventFilesSCHSResponse {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $url .= self::TASK_GET_DOCUMENTS;
        $options['query'] = [
            'searchId' => $dossierEventSCHS->getSearchId(),
            'documentTypeName' => $dossierEventSCHS->getDocumentTypeName(),
            'keyDataListList[1][0]' => $dossierEventSCHS->getEventId(),
        ];

        $options['extra']['job_event_metadata'] = new JobEventMetaData(
            service: self::TYPE_SERVICE,
            action: self::ACTION_SYNC_EVENTFILES,
            payload: $options['query'],
            signalementId: $affectation->getSignalement()->getId(),
            partnerId: $affectation->getPartner()->getId(),
            partnerType: $affectation->getPartner()->getType(),
        );

        $response = $this->request($url, $token, [], $options);

        if ($response instanceof JsonResponse) {
            throw new \Exception(json_decode($response->getContent())->message);
        }

        return new DossierEventFilesSCHSResponse($response);
    }

    public function preparePayloadPushDossier(DossierMessageSCHS $dossierMessage, bool $encodeDocuments = true): array
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
