<?php

namespace App\Dto\Request\Signalement;

use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\Signalement\SearchFilter;
use App\Service\UrlHelper;
use Symfony\Component\Validator\Constraints as Assert;

class SignalementSearchQuery
{
    public const int MAX_LIST_PAGINATION = 25;

    /**
     * @param array<mixed> $communes
     * @param array<mixed> $epcis
     * @param array<mixed> $etiquettes
     * @param array<mixed> $zones
     * @param array<mixed> $partenaires
     */
    public function __construct(
        private readonly ?string $territoire = null,
        private readonly ?string $searchTerms = null,
        #[Assert\Choice(['nouveau', 'en_cours', 'ferme', 'refuse'], message: 'Statut de signalement invalide')]
        private readonly ?string $status = null,
        private readonly ?array $communes = null,
        private readonly ?array $epcis = null,
        private readonly ?array $etiquettes = null,
        private readonly ?array $zones = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDepotDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateDepotFin = null,
        private readonly ?array $partenaires = null,
        private readonly ?string $bailleurSocial = null,
        #[Assert\Choice(['Non planifiée', 'Planifiée', 'Conclusion à renseigner', 'Terminée'], message: 'Statut de visite invalide')]
        private readonly ?string $visiteStatus = null,
        #[Assert\Choice(['partenaire', 'usager', 'automatique'])]
        private readonly ?string $typeDernierSuivi = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDernierSuiviDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateDernierSuiviFin = null,
        #[Assert\Choice(['accepte', 'en_attente', 'refuse', 'cloture_un_partenaire', 'cloture_tous_partenaire'], message: 'Statut d\'affectation invalide')]
        private readonly ?string $statusAffectation = null,
        #[Assert\GreaterThanOrEqual(0)]
        private readonly ?float $criticiteScoreMin = null,
        #[Assert\LessThanOrEqual(100)]
        private readonly ?float $criticiteScoreMax = null,
        #[Assert\Choice([
            'locataire',
            'bailleur_occupant',
            'tiers_particulier',
            'tiers_pro',
            'service_secours',
            'bailleur', ])]
        private readonly ?string $typeDeclarant = null,
        #[Assert\Choice(['privee', 'public', 'non_renseigne'], message: 'Nature du parc invalide')]
        private readonly ?string $natureParc = null,
        #[Assert\Choice(['caf', 'msa', 'oui', 'non', 'non_renseigne'], message: 'Allocataire invalide')]
        private readonly ?string $allocataire = null,
        #[Assert\Choice(['oui', 'non', 'non_renseigne'], message: 'Enfants de moins de 6 ans invalide')]
        private readonly ?string $enfantsM6 = null,
        #[Assert\Choice(['attente_relogement', 'bail_en_cours', 'preavis_de_depart', 'logement_vacant'], message: 'Situation invalide')]
        private readonly ?string $situation = null,
        #[Assert\Choice([
            'non_decence_energetique',
            'non_decence',
            'rsd',
            'danger',
            'insalubrite',
            'mise_en_securite_peril',
            'suroccupation',
            'assurantiel',
            'salete', ],
            message: 'Procédure suspectée invalide')]
        private readonly ?string $procedure = null,
        #[Assert\Choice([
            'non_decence',
            'rsd',
            'insalubrite',
            'mise_en_securite_peril',
            'logement_decent',
            'responsabilite_occupant_assurantiel',
            'salete',
            'autre', ],
            message: 'Procédure constatée invalide')]
        private readonly ?string $procedureConstatee = null,
        private readonly ?int $page = 1,
        #[Assert\Choice(['oui'], message: 'La valeur pour l\'affichage des signalements importés est invalide')]
        private readonly ?string $isImported = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour l\'affichage des zones est invalide')]
        private readonly ?string $isZonesDisplayed = null,
        private readonly ?bool $usagerAbandonProcedure = false,
        #[Assert\Choice(['reference', 'nomOccupant', 'lastSuiviAt', 'villeOccupant', 'createdAt'], message: 'Champ de tri invalide')]
        private readonly string $sortBy = 'reference',
        #[Assert\Choice(['ASC', 'DESC', 'asc', 'desc'], message: 'Direction de tri invalide')]
        private readonly string $direction = 'DESC',
        #[Assert\Choice([
            'abandon_de_procedure_absence_de_reponse',
            'depart_occupant',
            'insalubrite',
            'logement_decent',
            'logement_vendu',
            'non_decence',
            'peril',
            'refus_de_visite',
            'refus_de_travaux',
            'relogement_occupant',
            'responsabilite_de_l_occupant',
            'rsd',
            'travaux_faits_ou_en_cours',
            'doublon',
            'autre',
        ], message: 'Motif de clôture invalide')]
        private readonly ?string $motifCloture = null,
        #[Assert\Choice([
            TabDossier::CREATED_FROM_FORMULAIRE_USAGER,
            TabDossier::CREATED_FROM_FORMULAIRE_PRO,
            TabDossier::CREATED_FROM_FORMULAIRE_USAGER_V1,
            TabDossier::CREATED_FROM_FORMULAIRE_USAGER_V2,
            TabDossier::CREATED_FROM_FORMULAIRE_PRO_BO,
            TabDossier::CREATED_FROM_API,
            TabDossier::CREATED_FROM_IMPORT,
        ], message: 'Source de création invalide')]
        private readonly ?string $createdFrom = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour mes abonnements est invalide')]
        private readonly ?string $showMySignalementsOnly = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les relances usagers sans réponse est invalide')]
        private readonly ?string $relanceUsagerSansReponse = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les messages post-cloture est invalide')]
        private readonly ?string $isMessagePostCloture = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les nouveaux messages est invalide')]
        private readonly ?string $isNouveauMessage = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les messages sans réponse est invalide')]
        private readonly ?string $isMessageWithoutResponse = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les dossiers sans activité est invalide')]
        private readonly ?string $isDossiersSansActivite = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les adresses e-mail à vérifier est invalide')]
        private readonly ?string $isEmailAVerifier = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour les dossiers sans abonnements est invalide')]
        private readonly ?string $isDossiersSansAgent = null,
        #[Assert\Choice(['oui'], message: 'La valeur pour activité récente est invalide')]
        private readonly ?string $isActiviteRecente = null,
    ) {
    }

    public function getTerritoire(): ?string
    {
        return $this->territoire;
    }

    public function getSearchTerms(): ?string
    {
        return $this->searchTerms;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /** @return array<mixed> */
    public function getCommunes(): ?array
    {
        return $this->communes;
    }

    /** @return array<mixed> */
    public function getEpcis(): ?array
    {
        return $this->epcis;
    }

    /** @return array<mixed> */
    public function getEtiquettes(): ?array
    {
        return $this->etiquettes;
    }

    /** @return array<mixed> */
    public function getZones(): ?array
    {
        return $this->zones;
    }

    public function getDateDepotDebut(): ?string
    {
        return $this->dateDepotDebut;
    }

    public function getDateDepotFin(): ?string
    {
        return $this->dateDepotFin;
    }

    /** @return array<mixed> */
    public function getPartenaires(): ?array
    {
        return $this->partenaires;
    }

    public function getBailleurSocial(): ?string
    {
        return $this->bailleurSocial;
    }

    public function getVisiteStatus(): ?string
    {
        return $this->visiteStatus;
    }

    public function getTypeDernierSuivi(): ?string
    {
        return $this->typeDernierSuivi;
    }

    public function getDateDernierSuiviDebut(): ?string
    {
        return $this->dateDernierSuiviDebut;
    }

    public function getDateDernierSuiviFin(): ?string
    {
        return $this->dateDernierSuiviFin;
    }

    public function getStatusAffectation(): ?string
    {
        return $this->statusAffectation;
    }

    public function getCriticiteScoreMin(): ?float
    {
        return $this->criticiteScoreMin;
    }

    public function getCriticiteScoreMax(): ?float
    {
        return $this->criticiteScoreMax;
    }

    public function getTypeDeclarant(): ?string
    {
        return !empty($this->typeDeclarant) ? strtoupper($this->typeDeclarant) : null;
    }

    public function getNatureParc(): ?string
    {
        return $this->natureParc;
    }

    public function getAllocataire(): ?string
    {
        return !empty($this->allocataire) ? $this->allocataire : null;
    }

    public function getEnfantsM6(): ?string
    {
        return $this->enfantsM6;
    }

    public function getSituation(): ?string
    {
        return $this->situation;
    }

    public function getProcedure(): ?string
    {
        return !empty($this->procedure) ? strtoupper($this->procedure) : null;
    }

    public function getProcedureConstatee(): ?string
    {
        return !empty($this->procedureConstatee) ? strtoupper($this->procedureConstatee) : null;
    }

    public function getIsImported(): ?string
    {
        return $this->isImported;
    }

    public function getIsZonesDisplayed(): ?string
    {
        return $this->isZonesDisplayed;
    }

    public function getUsagerAbandonProcedure(): ?bool
    {
        return $this->usagerAbandonProcedure;
    }

    public function getMotifCloture(): ?string
    {
        return $this->motifCloture;
    }

    public function getShowMySignalementsOnly(): ?string
    {
        return $this->showMySignalementsOnly;
    }

    public function getRelanceUsagerSansReponse(): ?string
    {
        return $this->relanceUsagerSansReponse;
    }

    public function getIsNouveauMessage(): ?string
    {
        return $this->isNouveauMessage;
    }

    public function getIsMessagePostCloture(): ?string
    {
        return $this->isMessagePostCloture;
    }

    public function getIsMessageWithoutResponse(): ?string
    {
        return $this->isMessageWithoutResponse;
    }

    public function getIsDossiersSansActivite(): ?string
    {
        return $this->isDossiersSansActivite;
    }

    public function getIsEmailAVerifier(): ?string
    {
        return $this->isEmailAVerifier;
    }

    public function getIsDossiersSansAgent(): ?string
    {
        return $this->isDossiersSansAgent;
    }

    public function getIsActiviteRecente(): ?string
    {
        return $this->isActiviteRecente;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getCreatedFrom(): ?string
    {
        return $this->createdFrom;
    }

    /**
     * @todo: Après la MEP, s'appuyer exclusivement sur le DTO au lieu du tableau de filtres
     *
     * @see SearchFilter::buildFilters()
     *
     * @return array<mixed>
     */
    public function getFilters(): array
    {
        $filters = [];
        $filters['searchterms'] = $this->getSearchTerms() ?? null;
        $filters['territories'] = null !== $this->getTerritoire() ? [$this->getTerritoire()] : null;
        $filters['statuses'] = null !== $this->getStatus()
            ? [SignalementStatus::mapFilterStatus($this->getStatus())]
            : null;
        $filters['cities'] = $this->getCommunes() ?? null;
        $filters['epcis'] = $this->getEpcis() ?? null;
        $filters['partners'] = $this->getPartenaires() ?? null;
        $filters['allocs'] = null !== $this->getAllocataire() ? [$this->getAllocataire()] : null;
        $filters['housetypes'] = match ($this->getNatureParc()) {
            'public' => [1],
            'privee' => [0],
            'non_renseigne' => ['non_renseigne'],
            default => null,
        };
        $filters['enfantsM6'] = match ($this->getEnfantsM6()) {
            'oui' => [1],
            'non' => [0],
            'non_renseigne' => ['non_renseigne'],
            default => null,
        };

        if ('oui' === $this->getAllocataire()) {
            $filters['allocs'] = ['1', 'caf', 'msa'];
        } elseif ('non' === $this->getAllocataire()) {
            $filters['allocs'] = ['0'];
        }

        $filters['visites'] = null !== $this->getVisiteStatus() ? [$this->getVisiteStatus()] : null;
        if (null !== $this->getCriticiteScoreMin() || null !== $this->getCriticiteScoreMax()) {
            $filters['scores'] = [
                'on' => $this->getCriticiteScoreMin() ?? 0,
                'off' => $this->getCriticiteScoreMax() ?? 100,
            ];
        }
        if (null !== $this->getDateDepotDebut() || null !== $this->getDateDepotFin()) {
            $filters['dates'] = [
                'on' => $this->getDateDepotDebut(),
                'off' => $this->getDateDepotFin(),
            ];
        }
        $filters['tags'] = $this->getEtiquettes() ?? null;
        $filters['zones'] = $this->getZones() ?? null;
        $filters['typeDeclarant'] = $this->getTypeDeclarant();
        $filters['situation'] = $this->getSituation();
        $filters['procedure'] = $this->getProcedure();
        $filters['procedureConstatee'] = $this->getProcedureConstatee();
        $filters['typeDernierSuivi'] = $this->getTypeDernierSuivi();
        if (null !== $this->getDateDernierSuiviDebut() || null !== $this->getDateDernierSuiviFin()) {
            $filters['datesDernierSuivi'] = [
                'on' => $this->getDateDernierSuiviDebut(),
                'off' => $this->getDateDernierSuiviFin(),
            ];
        }
        $filters['statusAffectation'] = $this->getStatusAffectation();
        $filters['closed_affectation'] = match ($filters['statusAffectation']) {
            'cloture_un_partenaire' => ['ONE_CLOSED'],
            'cloture_tous_partenaire' => ['ALL_CLOSED'],
            default => null,
        };

        $filters['isImported'] = match ($this->getIsImported()) {
            'oui' => true,
            default => null,
        };

        $filters['isZonesDisplayed'] = match ($this->getIsZonesDisplayed()) {
            'oui' => true,
            default => null,
        };

        $filters['usager_abandon_procedure'] = $this->getUsagerAbandonProcedure();
        $filters['bailleurSocial'] = $this->getBailleurSocial();
        $filters['motifCloture'] = $this->getMotifCloture();
        $filters['createdFrom'] = $this->getCreatedFrom();
        $filters['showMySignalementsOnly'] = 'oui' === $this->getShowMySignalementsOnly();
        $filters['relanceUsagerSansReponse'] = 'oui' === $this->getRelanceUsagerSansReponse();
        $filters['isNouveauMessage'] = 'oui' === $this->getIsNouveauMessage();
        $filters['isMessagePostCloture'] = 'oui' === $this->getIsMessagePostCloture();
        $filters['isMessageWithoutResponse'] = 'oui' === $this->getIsMessageWithoutResponse();
        $filters['isDossiersSansActivite'] = 'oui' === $this->getIsDossiersSansActivite();
        $filters['isEmailAVerifier'] = 'oui' === $this->getIsEmailAVerifier();
        $filters['isDossiersSansAgent'] = 'oui' === $this->getIsDossiersSansAgent();
        $filters['isActiviteRecente'] = 'oui' === $this->getIsActiviteRecente();
        $filters['page'] = $this->getPage() ?? 1;
        $filters['maxItemsPerPage'] = self::MAX_LIST_PAGINATION;
        $filters['sortBy'] = $this->getSortBy();
        $filters['orderBy'] = $this->getDirection();

        return array_filter($filters);
    }

    public function getQueryStringForUrl(): string
    {
        $params = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (null !== $value) {
                $params[$key] = $value;
            }
        }
        if (isset($params['page']) && 1 === $params['page']) {
            unset($params['page']);
        }

        return UrlHelper::arrayToQueryString($params);
    }

    public static function fromParams(array $params): self
    {
        return new self(
            territoire: $params['territoire'] ?? null,
            searchTerms: $params['searchTerms'] ?? null,
            status: $params['status'] ?? null,
            communes: isset($params['communes']) && is_array($params['communes']) ? $params['communes'] : null,
            epcis: isset($params['epcis']) && is_array($params['epcis']) ? $params['epcis'] : null,
            etiquettes: isset($params['etiquettes']) && is_array($params['etiquettes']) ? $params['etiquettes'] : null,
            zones: isset($params['zones']) && is_array($params['zones']) ? $params['zones'] : null,
            dateDepotDebut: $params['dateDepotDebut'] ?? null,
            dateDepotFin: $params['dateDepotFin'] ?? null,
            partenaires: isset($params['partenaires']) && is_array($params['partenaires']) ? $params['partenaires'] : null,
            bailleurSocial: $params['bailleurSocial'] ?? null,
            visiteStatus: $params['visiteStatus'] ?? null,
            typeDernierSuivi: $params['typeDernierSuivi'] ?? null,
            dateDernierSuiviDebut: $params['dateDernierSuiviDebut'] ?? null,
            dateDernierSuiviFin: $params['dateDernierSuiviFin'] ?? null,
            statusAffectation: $params['statusAffectation'] ?? null,
            criticiteScoreMin: isset($params['criticiteScoreMin']) ? (float) $params['criticiteScoreMin'] : null,
            criticiteScoreMax: isset($params['criticiteScoreMax']) ? (float) $params['criticiteScoreMax'] : null,
            typeDeclarant: $params['typeDeclarant'] ?? null,
            natureParc: $params['natureParc'] ?? null,
            allocataire: $params['allocataire'] ?? null,
            enfantsM6: $params['enfantsM6'] ?? null,
            situation: $params['situation'] ?? null,
            procedure: $params['procedure'] ?? null,
            procedureConstatee: $params['procedureConstatee'] ?? null,
            page: isset($params['page']) ? (int) $params['page'] : 1,
            isImported: $params['isImported'] ?? null,
            isZonesDisplayed: $params['isZonesDisplayed'] ?? null,
            usagerAbandonProcedure: isset($params['usagerAbandonProcedure']) ? (bool) $params['usagerAbandonProcedure'] : false,
            sortBy: $params['sortBy'] ?? 'reference',
            direction: $params['direction'] ?? 'DESC',
            motifCloture: $params['motifCloture'] ?? null,
            createdFrom: $params['createdFrom'] ?? null,
            showMySignalementsOnly: $params['showMySignalementsOnly'] ?? null,
            relanceUsagerSansReponse: $params['relanceUsagerSansReponse'] ?? null,
            isMessagePostCloture: $params['isMessagePostCloture'] ?? null,
            isNouveauMessage: $params['isNouveauMessage'] ?? null,
            isMessageWithoutResponse: $params['isMessageWithoutResponse'] ?? null,
            isDossiersSansActivite: $params['isDossiersSansActivite'] ?? null,
            isEmailAVerifier: $params['isEmailAVerifier'] ?? null,
            isDossiersSansAgent: $params['isDossiersSansAgent'] ?? null,
            isActiviteRecente: $params['isActiviteRecente'] ?? null,
        );
    }
}
