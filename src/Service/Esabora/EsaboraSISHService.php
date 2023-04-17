<?php

namespace App\Service\Esabora;

use App\Messenger\Message\DossierMessageSISH;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraSISHService extends AbstractEsaboraService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($this->client, $this->logger);
    }

    public function pushAdresse(DossierMessageSISH $dossierMessageSISH): ResponseInterface|JsonResponse
    {
        $url = $dossierMessageSISH->getUrl();
        $token = $dossierMessageSISH->getToken();

        $payload = [
            'treatmentName' => 'SISH_ADRESSE',
            'fieldList' => $this->preparePayloadPushAdresse($dossierMessageSISH),
        ];

        return $this->request($url, $token, AbstractEsaboraService::TASK_INSERT, $payload);
    }

    public function pushDossier(DossierMessageSISH $dossierMessageSISH): ResponseInterface|JsonResponse
    {
        $url = $dossierMessageSISH->getUrl();
        $token = $dossierMessageSISH->getToken();

        $payload = [
            'treatmentName' => 'SISH_DOSSIER',
            'fieldList' => $this->preparePayloadPushDossier($dossierMessageSISH),
        ];

        return $this->request($url, $token, AbstractEsaboraService::TASK_INSERT, $payload);
    }

    public function pushPersonne(DossierMessageSISH $dossierMessageSISH): ResponseInterface|JsonResponse
    {
        $url = $dossierMessageSISH->getUrl();
        $token = $dossierMessageSISH->getToken();

        $payload = [
            'treatmentName' => 'SISH_DOSSIER_PERSONNE',
            'fieldList' => $this->preparePayloadPushPersonne($dossierMessageSISH),
        ];

        return $this->request($url, $token, AbstractEsaboraService::TASK_INSERT, $payload);
    }

    public function getStateDossier(): void
    {
        // WS_ETAT_DOSSIER_SAS
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
                'fieldName' => 'Localisation_CodePostal',
                'fieldValue' => $dossierMessageSISH->getLocalisationVille(),
            ],
            [
                'fieldName' => 'Localisation_Insee',
                'fieldValue' => $dossierMessageSISH->getLocalisationLocalisationInsee(),
            ],
        ];
    }

    private function preparePayloadPushDossier(DossierMessageSISH $dossierMessageSISH): array
    {
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
                'fieldValue' => $dossierMessageSISH->getSignalementDetails(),
            ],
            [
                'fieldName' => 'Signalement_Problemes',
                'fieldValue' => $dossierMessageSISH->getSignalementProblemes(),
            ],
            [
                'fieldName' => 'PJ_Observations',
                'fieldValue' => $dossierMessageSISH->getPiecesJointesObservation(),
            ],
            [
                'fieldName' => 'PJ_Documents',
                'fieldDocumentUpdate' => 1,
                'fieldValue' => [],
            ],
        ];
    }

    private function preparePayloadPushPersonne(DossierMessageSISH $dossierMessageSISH): array
    {
        return [
            [
                'fieldName' => 'Sas_Dossier_ID',
                'fieldValue' => $dossierMessageSISH->getSasDossierId(),
            ],
            [
                'fieldName' => 'Personne_Type',
                'fieldValue' => $dossierMessageSISH->getPersonneType(),
            ],
            [
                'fieldName' => 'Personne_Nom',
                'fieldValue' => $dossierMessageSISH->getPersonneNom(),
            ],
            [
                'fieldName' => 'Personne_Prenom',
                'fieldValue' => $dossierMessageSISH->getPersonnePrenom(),
            ],
            [
                'fieldName' => 'Personne_Telephone',
                'fieldValue' => $dossierMessageSISH->getPersonneTelephone(),
            ],
            [
                'fieldName' => 'Personne_Mail',
                'fieldValue' => $dossierMessageSISH->getPersonneEmail(),
            ],
            [
                'fieldName' => 'Personne_LienOccupant',
                'fieldValue' => $dossierMessageSISH->getPersonneLienOccupant(),
            ],
            [
                'fieldName' => 'Personne_Structure',
                'fieldValue' => $dossierMessageSISH->getPersonneStructure(),
            ],
            [
                'fieldName' => 'Personne_Adresse',
                'fieldValue' => $dossierMessageSISH->getPersonneAdresse(),
            ],
            [
                'fieldName' => 'Personne_Representant',
                'fieldValue' => $dossierMessageSISH->getPersonneRepresentant(),
            ],
        ];
    }
}
