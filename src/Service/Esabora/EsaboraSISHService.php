<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Esabora\Response\DossierPushSISHResponse;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use App\Service\Esabora\Response\DossierVisiteSISHCollectionResponse;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Transliterator\EmojiTransliterator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EsaboraSISHService extends AbstractEsaboraService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly UploadHandlerService $uploadHandlerService,
    ) {
        parent::__construct($this->client, $this->logger);
    }

    public function pushAdresse(DossierMessageSISH $dossierMessageSISH): DossierPushSISHResponse
    {
        $url = $dossierMessageSISH->getUrl();
        $token = $dossierMessageSISH->getToken();

        $payload = [
            'treatmentName' => 'SISH_ADRESSE',
            'fieldList' => $this->preparePayloadPushAdresse($dossierMessageSISH),
        ];

        return $this->getDossierPushResponse($url, $token, $payload);
    }

    public function pushDossier(DossierMessageSISH $dossierMessageSISH): DossierPushSISHResponse
    {
        $url = $dossierMessageSISH->getUrl();
        $token = $dossierMessageSISH->getToken();

        $payload = [
            'treatmentName' => 'SISH_DOSSIER',
            'fieldList' => $this->preparePayloadPushDossier($dossierMessageSISH),
        ];

        return $this->getDossierPushResponse($url, $token, $payload);
    }

    public function pushPersonne(
        DossierMessageSISH $dossierMessageSISH,
        DossierMessageSISHPersonne $dossierMessageSISHPersonne
    ): DossierPushSISHResponse {
        $url = $dossierMessageSISH->getUrl();
        $token = $dossierMessageSISH->getToken();

        $payload = [
            'treatmentName' => 'SISH_DOSSIER_PERSONNE',
            'fieldList' => $this->preparePayloadPushPersonne($dossierMessageSISH, $dossierMessageSISHPersonne),
        ];

        return $this->getDossierPushResponse($url, $token, $payload);
    }

    public function getStateDossier(Affectation $affectation): DossierStateSISHResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $payload = [
            'searchName' => 'SISH_ETAT_DOSSIER_SAS',
            'criterionList' => [
                [
                    'criterionName' => 'Reference_Dossier',
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

            return new DossierStateSISHResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR !== $statusCode
                    ? $response->toArray(throw: false)
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return new DossierStateSISHResponse(
            ['message' => $exception->getMessage(), 'status_code' => $statusCode],
            $statusCode
        );
    }

    public function getVisiteDossier(Affectation $affectation): DossierVisiteSISHCollectionResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;

        try {
            $response = $this->request(
                $url,
                $token,
                $this->prepareInterventionPayload($affectation, self::SISH_VISITES_DOSSIER_SAS)
            );

            $statusCode = $response->getStatusCode();

            return new DossierVisiteSISHCollectionResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR !== $statusCode
                    ? $response->toArray(throw: false)
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return new DossierVisiteSISHCollectionResponse(
            ['message' => $exception->getMessage(), 'status_code' => $statusCode],
            $statusCode
        );
    }

    public function getArreteDossier(Affectation $affectation): DossierArreteSISHCollectionResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;

        try {
            $response = $this->request(
                $url,
                $token,
                $this->prepareInterventionPayload($affectation, self::SISH_ARRETES_DOSSIER_SAS)
            );

            $statusCode = $response->getStatusCode();

            return new DossierArreteSISHCollectionResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR !== $statusCode
                    ? $response->toArray(throw: false)
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return new DossierArreteSISHCollectionResponse(
            ['message' => $exception->getMessage(), 'status_code' => $statusCode],
            $statusCode
        );
    }

    private function getDossierPushResponse(string $url, string $token, array $payload): DossierPushSISHResponse
    {
        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        try {
            $response = $this->request($url, $token, $payload);
            $statusCode = $response->getStatusCode();

            return new DossierPushSISHResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR >= $statusCode
                    ? $response->toArray(throw: false)
                    : [],
                $statusCode
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return new DossierPushSISHResponse(['message' => $exception->getMessage()], $statusCode);
    }

    private function preparePayloadPushAdresse(DossierMessageSISH $dossierMessageSISH): array
    {
        return [
            [
                'fieldName' => 'Reference_Adresse',
                'fieldValue' => $dossierMessageSISH->getReferenceAdresse(),
            ],
            [
                'fieldName' => 'Localisation_Numero',
                'fieldValue' => $dossierMessageSISH->getLocalisationNumero(),
            ],
            [
                'fieldName' => 'Localisation_NumeroExt',
                'fieldValue' => $dossierMessageSISH->getLocalisationNumeroExt(),
            ],
            [
                'fieldName' => 'Localisation_Adresse1',
                'fieldValue' => $dossierMessageSISH->getLocalisationAdresse1(),
            ],
            [
                'fieldName' => 'Localisation_Adresse2',
                'fieldValue' => $dossierMessageSISH->getLocalisationAdresse2(),
            ],
            [
                'fieldName' => 'Localisation_Adresse3',
                'fieldValue' => $dossierMessageSISH->getLocalisationAdresse3(),
            ],
            [
                'fieldName' => 'Localisation_CodePostal',
                'fieldValue' => $dossierMessageSISH->getLocalisationCodePostal(),
            ],
            [
                'fieldName' => 'Localisation_Ville',
                'fieldValue' => $dossierMessageSISH->getLocalisationVille(),
            ],
            [
                'fieldName' => 'Localisation_Insee',
                'fieldValue' => $dossierMessageSISH->getLocalisationLocalisationInsee(),
            ],
        ];
    }

    private function preparePayloadPushDossier(
        DossierMessageSISH $dossierMessageSISH,
        bool $encodeDocuments = true
    ): array {
        $piecesJointes = [];
        if ($encodeDocuments) {
            $piecesJointes = array_map(function ($pieceJointe) {
                $filepath = $this->uploadHandlerService->getTmpFilepath($pieceJointe['documentContent']);
                $pieceJointe['documentContent'] = base64_encode(file_get_contents($filepath));

                return $pieceJointe;
            }, $dossierMessageSISH->getPiecesJointesDocuments());
        }

        $transliterator = EmojiTransliterator::create('strip');

        return [
            [
                'fieldName' => 'Sas_Adresse',
                'fieldValue' => $dossierMessageSISH->getSasAdresse(),
            ],
            [
                'fieldName' => 'Sas_LogicielProvenance',
                'fieldValue' => $dossierMessageSISH->getSasLogicielProvenance(),
            ],
            [
                'fieldName' => 'Reference_Dossier',
                'fieldValue' => $dossierMessageSISH->getReferenceDossier(),
            ],
            [
                'fieldName' => 'Sas_TypeDossier',
                'fieldValue' => $dossierMessageSISH->getSasTypeDossier(),
            ],
            [
                'fieldName' => 'Sas_DateAffectation',
                'fieldValue' => $dossierMessageSISH->getSasDateAffectation(),
            ],
            [
                'fieldName' => 'Localisation_Etage',
                'fieldValue' => $dossierMessageSISH->getLocalisationEtage(),
            ],
            [
                'fieldName' => 'Localisation_Escalier',
                'fieldValue' => $dossierMessageSISH->getLocalisationEscalier(),
            ],
            [
                'fieldName' => 'Localisation_NumPorte',
                'fieldValue' => $dossierMessageSISH->getLocalisationNumPorte(),
            ],
            [
                'fieldName' => 'SitOccupant_NbAdultes',
                'fieldValue' => $dossierMessageSISH->getSitOccupantNbAdultes(),
            ],
            [
                'fieldName' => 'SitOccupant_NbEnfantsM6',
                'fieldValue' => $dossierMessageSISH->getSitOccupantNbEnfantsM6(),
            ],
            [
                'fieldName' => 'SitOccupant_NbEnfantsP6',
                'fieldValue' => $dossierMessageSISH->getSitOccupantNbEnfantsP6(),
            ],
            [
                'fieldName' => 'SitOccupant_NbOccupants',
                'fieldValue' => $dossierMessageSISH->getSitOccupantNbOccupants(),
            ],
            [
                'fieldName' => 'SitOccupant_NumAllocataire',
                'fieldValue' => $dossierMessageSISH->getSitOccupantNumAllocataire(),
            ],
            [
                'fieldName' => 'SitOccupant_MontantAlloc',
                'fieldValue' => $dossierMessageSISH->getSitOccupantMontantAllocation(),
            ],
            [
                'fieldName' => 'SitLogement_BailEncours',
                'fieldValue' => $dossierMessageSISH->getSitLogementBailEncours(),
            ],
            [
                'fieldName' => 'SitLogement_BailDateEntree',
                'fieldValue' => $dossierMessageSISH->getSitLogementBailDateEntree(),
            ],
            [
                'fieldName' => 'SitLogement_PreavisDepart',
                'fieldValue' => $dossierMessageSISH->getSitLogementPreavisDepart(),
            ],
            [
                'fieldName' => 'SitLogement_Relogement',
                'fieldValue' => $dossierMessageSISH->getSitLogementRelogement(),
            ],
            [
                'fieldName' => 'SitLogement_Superficie',
                'fieldValue' => $dossierMessageSISH->getSitLogementSuperficie(),
            ],
            [
                'fieldName' => 'SitLogement_MontantLoyer',
                'fieldValue' => $dossierMessageSISH->getSitLogementMontantLoyer(),
            ],
            [
                'fieldName' => 'Declarant_NonOccupant',
                'fieldValue' => $dossierMessageSISH->getDeclarantNonOccupant(),
            ],
            [
                'fieldName' => 'Logement_Nature',
                'fieldValue' => $dossierMessageSISH->getLogementNature(),
            ],
            [
                'fieldName' => 'Logement_Type',
                'fieldValue' => $dossierMessageSISH->getLogementType(),
            ],
            [
                'fieldName' => 'Logement_Social',
                'fieldValue' => $dossierMessageSISH->getLogementSocial(),
            ],
            [
                'fieldName' => 'Logement_AnneeConstr',
                'fieldValue' => $dossierMessageSISH->getLogementAnneeConstruction(),
            ],
            [
                'fieldName' => 'Logement_TypeEnergie',
                'fieldValue' => $dossierMessageSISH->getLogementTypeEnergie(),
            ],
            [
                'fieldName' => 'Logement_Collectif',
                'fieldValue' => $dossierMessageSISH->getLogementCollectif(),
            ],
            [
                'fieldName' => 'Logement_Avant1949',
                'fieldValue' => $dossierMessageSISH->getLogementAvant1949(),
            ],
            [
                'fieldName' => 'Logement_DiagST',
                'fieldValue' => $dossierMessageSISH->getLogementDiagST(),
            ],
            [
                'fieldName' => 'Logement_Invariant',
                'fieldValue' => $dossierMessageSISH->getLogementInvariant(),
            ],
            [
                'fieldName' => 'Logement_NbPieces',
                'fieldValue' => $dossierMessageSISH->getLogementNbPieces(),
            ],
            [
                'fieldName' => 'Logement_NbChambres',
                'fieldValue' => $dossierMessageSISH->getLogementNbChambres(),
            ],
            [
                'fieldName' => 'Logement_NbNiveaux',
                'fieldValue' => $dossierMessageSISH->getLogementNbPieces(),
            ],
            [
                'fieldName' => 'Proprietaire_Averti',
                'fieldValue' => $dossierMessageSISH->getProprietaireAverti(),
            ],
            [
                'fieldName' => 'Proprietaire_AvertiDate',
                'fieldValue' => $dossierMessageSISH->getProprietaireAvertiDate(),
            ],
            [
                'fieldName' => 'Proprietaire_AvertiMoyen',
                'fieldValue' => $dossierMessageSISH->getProprietaireAvertiMoyen(),
            ],
            [
                'fieldName' => 'Signalement_Score',
                'fieldValue' => $dossierMessageSISH->getSignalementScore(),
            ],
            [
                'fieldName' => 'Signalement_Origine',
                'fieldValue' => $dossierMessageSISH->getSignalementOrigine(),
            ],
            [
                'fieldName' => 'Signalement_Numero',
                'fieldValue' => $dossierMessageSISH->getSignalementNumero(),
            ],
            [
                'fieldName' => 'Signalement_Commentaire',
                'fieldValue' => $dossierMessageSISH->getSignalementCommentaire(),
            ],
            [
                'fieldName' => 'Signalement_Date',
                'fieldValue' => $dossierMessageSISH->getSignalementDate(),
            ],
            [
                'fieldName' => 'Signalement_Details',
                'fieldValue' => $transliterator->transliterate((string) $dossierMessageSISH->getSignalementDetails()),
            ],
            [
                'fieldName' => 'Signalement_Problemes',
                'fieldValue' => $dossierMessageSISH->getSignalementProblemes(),
            ],
            [
                'fieldName' => 'Signalement_URL',
                'fieldValue' => $dossierMessageSISH->getSignalementUrl(),
            ],
            [
                'fieldName' => 'PJ_Observations',
                'fieldValue' => $dossierMessageSISH->getPiecesJointesObservation(),
            ],
            [
                'fieldName' => 'PJ_Documents',
                'fieldDocumentUpdate' => 1,
                'fieldValue' => $piecesJointes,
            ],
        ];
    }

    public function preparePayloadPushPersonne(
        DossierMessageSISH $dossierMessageSISH,
        DossierMessageSISHPersonne $dossierMessageSISHPersonne
    ): array {
        return [
            [
                'fieldName' => 'Sas_Dossier_ID',
                'fieldValue' => $dossierMessageSISH->getSasDossierId(),
            ],
            [
                'fieldName' => 'Personne_Type',
                'fieldValue' => $dossierMessageSISHPersonne->getType(),
            ],
            [
                'fieldName' => 'Personne_Nom',
                'fieldValue' => $dossierMessageSISHPersonne->getNom(),
            ],
            [
                'fieldName' => 'Personne_Prenom',
                'fieldValue' => $dossierMessageSISHPersonne->getPrenom(),
            ],
            [
                'fieldName' => 'Personne_Telephone',
                'fieldValue' => $dossierMessageSISHPersonne->getTelephone(),
            ],
            [
                'fieldName' => 'Personne_Mail',
                'fieldValue' => $dossierMessageSISHPersonne->getEmail(),
            ],
            [
                'fieldName' => 'Personne_LienOccupant',
                'fieldValue' => $dossierMessageSISHPersonne->getLienOccupant(),
            ],
            [
                'fieldName' => 'Personne_Structure',
                'fieldValue' => $dossierMessageSISHPersonne->getStructure(),
            ],
            [
                'fieldName' => 'Personne_Adresse',
                'fieldValue' => $dossierMessageSISHPersonne->getAdresse(),
            ],
            [
                'fieldName' => 'Personne_Representant',
                'fieldValue' => $dossierMessageSISHPersonne->getRepresentant(),
            ],
        ];
    }
}
