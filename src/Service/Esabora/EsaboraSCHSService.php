<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraSCHSService extends AbstractEsaboraService
{
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
