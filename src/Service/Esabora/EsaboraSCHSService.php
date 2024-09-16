<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Service\Esabora\Response\DossierEventFilesSCHSResponse;
use App\Service\Esabora\Response\DossierEventsSCHSResponse;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Esabora\Response\Model\DossierEventSCHS;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraSCHSService extends AbstractEsaboraService
{
    private const ACTION_SYNC_EVENTS = 'sync_events';
    private const ACTION_SYNC_EVENTFILES = 'sync_eventfiles';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly JobEventManager $jobEventManager,
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

        return $this->request($url, $token, $payload);
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

        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        try {
            $response = $this->request($url, $token, $payload);
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

    public function getDossierEvents(Affectation $affectation): DossierEventsSCHSResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
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
        $response = $this->request($url, $token, $payload);
        $this->saveJobEvent(self::ACTION_SYNC_EVENTS, json_encode($payload), $response, $affectation);
        if ($response instanceof JsonResponse) {
            throw new \Exception(json_decode($response->getContent())->message);
        }

        return new DossierEventsSCHSResponse($response, $affectation);
    }

    public function getDossierEventFiles(DossierEventSCHS $event): DossierEventFilesSCHSResponse
    {
        list($url, $token) = $event->getDossierEvents()->getAffectation()->getPartner()->getEsaboraCredential();
        $url .= self::TASK_GET_DOCUMENTS;
        $queryParameters = [
            'searchId' => $event->getDossierEvents()->getSearchId(),
            'documentTypeName' => $event->getDossierEvents()->getDocumentTypeName(),
            'keyDataListList[1][0]' => $event->getEventId(),
        ];
        $response = $this->request($url, $token, [], ['query' => $queryParameters]);
        $this->saveJobEvent(self::ACTION_SYNC_EVENTFILES, json_encode($queryParameters), $response, $event->getDossierEvents()->getAffectation());
        if ($response instanceof JsonResponse) {
            throw new \Exception(json_decode($response->getContent())->message);
        }

        return new DossierEventFilesSCHSResponse($response);
    }

    private function saveJobEvent(string $action, string $message, ResponseInterface|JsonResponse $response, Affectation $affectation): void
    {
        $responseEncoded = null;
        if ($response instanceof JsonResponse) {
            $responseEncoded = $response->getContent();
        } elseif (Response::HTTP_OK === $response->getStatusCode()) {
            $responseEncoded = $response->toArray();
            if (isset($responseEncoded['rowList']) && isset($responseEncoded['rowList'][0]) && isset($responseEncoded['rowList'][0]['documentZipContent'])) {
                unset($responseEncoded['rowList'][0]['documentZipContent']);
            }
            $responseEncoded = json_encode($responseEncoded);
        }
        $this->jobEventManager->createJobEvent(
            AbstractEsaboraService::TYPE_SERVICE,
            $action,
            $message,
            $responseEncoded,
            Response::HTTP_OK === $response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            $response->getStatusCode(),
            $affectation->getSignalement()->getId(),
            $affectation->getPartner()->getId(),
            $affectation->getPartner()->getType(),
        );
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
