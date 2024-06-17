<?php

namespace App\Dto\Request\Signalement;

use App\Entity\Enum\SignalementStatus;
use App\Service\Signalement\SearchFilter;
use Symfony\Component\Validator\Constraints as Assert;

class SignalementSearchQuery
{
    public const MAX_LIST_PAGINATION = 25;

    public function __construct(
        private readonly ?string $territoire = null,
        private readonly ?string $searchTerms = null,
        #[Assert\Choice(['nouveau', 'en_cours', 'ferme', 'refuse'])]
        private readonly ?string $status = null,
        private readonly ?array $communes = null,
        private readonly ?array $epcis = null,
        private readonly ?array $etiquettes = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDepotDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateDepotFin = null,
        private readonly ?array $partenaires = null,
        #[Assert\Choice(['Non planifiée', 'Planifiée', 'Conclusion à renseigner', 'Terminée'])]
        private readonly ?string $visiteStatus = null,
        #[Assert\Choice(['partenaire', 'usager', 'automatique'])]
        private readonly ?string $typeDernierSuivi = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDernierSuiviDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateDernierSuiviFin = null,
        #[Assert\Choice(['accepte', 'en_attente', 'refuse', 'cloture_un_partenaire', 'cloture_tous_partenaire'])]
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
        #[Assert\Choice(['privee', 'public', 'non_renseigne'])]
        private readonly ?string $natureParc = null,
        #[Assert\Choice(['caf', 'msa', 'oui', 'non', 'non_renseigne'])]
        private readonly ?string $allocataire = null,
        #[Assert\Choice(['oui', 'non', 'non_renseigne'])]
        private readonly ?string $enfantsM6 = null,
        #[Assert\Choice(['attente_relogement', 'bail_en_cours', 'preavis_de_depart'])]
        private readonly ?string $situation = null,
        #[Assert\Choice([
            'non_decence_energetique',
            'non_decence',
            'rsd',
            'danger',
            'insalubrite',
            'mise_en_securite_peril',
            'suroccupation', ])]
        private readonly ?string $procedure = null,
        private readonly ?int $page = 1,
        #[Assert\Choice(['oui'])]
        private readonly ?string $isImported = null,
        #[Assert\Choice(['NO_SUIVI_AFTER_3_RELANCES'])]
        private readonly ?string $relancesUsager = null,
        #[Assert\Choice(['oui'])]
        private readonly ?string $nouveauSuivi = null,
        private readonly ?int $sansSuiviPeriode = null,
        #[Assert\Choice(['reference', 'nomOccupant', 'lastSuiviAt'])]
        private readonly string $sortBy = 'reference',
        #[Assert\Choice(['ASC', 'DESC', 'asc', 'desc'])]
        private readonly string $orderBy = 'DESC',
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

    public function getCommunes(): ?array
    {
        return $this->communes;
    }

    public function getEpcis(): ?array
    {
        return $this->epcis;
    }

    public function getEtiquettes(): ?array
    {
        return $this->etiquettes;
    }

    public function getDateDepotDebut(): ?string
    {
        return $this->dateDepotDebut;
    }

    public function getDateDepotFin(): ?string
    {
        return $this->dateDepotFin;
    }

    public function getPartenaires(): ?array
    {
        return $this->partenaires;
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

    public function getIsImported(): ?string
    {
        return $this->isImported;
    }

    public function getRelancesUsager(): ?string
    {
        return $this->relancesUsager;
    }

    public function getNouveauSuivi(): ?string
    {
        return $this->nouveauSuivi;
    }

    public function getSansSuiviPeriode(): ?int
    {
        return $this->sansSuiviPeriode;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    /**
     * @todo: Après la MEP, s'appuyer exclusivement sur le DTO au lieu du tableau de filtres
     *
     * @see SearchFilter::buildFilters()
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
            default => null
        };
        $filters['enfantsM6'] = match ($this->getEnfantsM6()) {
            'oui' => [1],
            'non' => [0],
            'non_renseigne' => ['non_renseigne'],
            default => null
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
        $filters['typeDeclarant'] = $this->getTypeDeclarant();
        $filters['situation'] = $this->getSituation();
        $filters['procedure'] = $this->getProcedure();
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
            default => null
        };

        $filters['isImported'] = match ($this->getIsImported()) {
            'oui' => true,
            default => null
        };

        $filters['relances_usager'] = [$this->getRelancesUsager()];
        $filters['delays'] = $this->getSansSuiviPeriode();
        $filters['nouveau_suivi'] = $this->getNouveauSuivi();

        $filters['page'] = $this->getPage() ?? 1;
        $filters['maxItemsPerPage'] = self::MAX_LIST_PAGINATION;
        $filters['sortBy'] = $this->getSortBy() ?? 'reference';
        $filters['orderBy'] = $this->getOrderBy() ?? 'DESC';

        return array_filter($filters);
    }
}
