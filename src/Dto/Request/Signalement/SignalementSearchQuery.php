<?php

namespace App\Dto\Request\Signalement;

use App\Entity\Enum\SignalementStatus;
use Symfony\Component\Validator\Constraints as Assert;

class SignalementSearchQuery
{
    public function __construct(
        private readonly ?string $territory = null,
        private readonly ?string $searchTerms = null,
        #[Assert\Choice(['nouveau', 'en cours', 'ferme', 'refuse'])]
        private readonly ?string $status = null,
        private readonly ?string $commune = null,
        private readonly ?array $etiquettes = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDepotDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateDepotFin = null,
        private readonly ?array $partenaires = null,
        #[Assert\Choice(['Non planifiée', 'Planifiée', 'Conclusion à renseigner', 'Terminée'])]
        private readonly ?string $visiteStatus = null,
        private readonly ?string $typeDernierSuivi = null,
        private readonly ?string $statusAffectations = null,
        #[Assert\GreaterThanOrEqual(0)]
        private readonly ?float $criticiteScoreMin = null,
        #[Assert\LessThanOrEqual(100)]
        private readonly ?float $criticiteScoreMax = null,
        #[Assert\Choice(['locataire', 'bailleur_occupant', 'tiers_particulier', 'tiers_pro', 'service_secours', 'bailleur'])]
        private readonly ?string $typeDeclarant = null,
        #[Assert\Choice(['privee', 'public'])]
        private readonly ?string $natureParc = null,
        #[Assert\Choice(['caf', 'msa', 'non'])]
        private readonly ?string $allocataire = null,
        private readonly ?bool $enfantsM6 = null,
        private readonly ?string $situation = null,
        private readonly ?int $page = 1,
        #[Assert\Choice(['reference', 'nomOccupant', 'createdAt'])]
        private readonly string $sortBy = 'reference',
        private readonly string $orderBy = 'DESC',
        private readonly int $maxItemPerPage = 25,
    ) {
    }

    public function getTerritory(): ?string
    {
        return $this->territory;
    }

    public function getSearchTerms(): ?string
    {
        return $this->searchTerms;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getCommune(): ?string
    {
        return $this->commune;
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

    public function getStatusAffectations(): ?string
    {
        return $this->statusAffectations;
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
        return !empty($this->allocataire) ? strtoupper($this->allocataire) : null;
    }

    public function getEnfantsM6(): ?bool
    {
        return $this->enfantsM6;
    }

    public function getSituation(): ?string
    {
        return $this->situation;
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

    public function getMaxItemPerPage(): int
    {
        return $this->maxItemPerPage;
    }

    public function getFilters(): array
    {
        $filters = [];
        $filters['searchterms'] = $this->getSearchTerms() ?? null;
        $filters['territories'] = $this->getTerritory() ?? null;
        $filters['statuses'] = null !== $this->getStatus()
            ? [SignalementStatus::mapFilterStatus($this->getStatus())]
            : null;
        $filters['cities'] = null !== $this->getCommune() ? [$this->getCommune()] : null;
        $filters['partners'] = $this->getPartenaires() ?? null;
        $filters['allocs'] = null !== $this->getAllocataire() ? [$this->getAllocataire()] : null;
        $filters['housetypes'] = null !== $this->getNatureParc() ? ['public' === $this->getNatureParc()] : null;
        $filters['enfantsM6'] = null !== $this->getEnfantsM6() ? [$this->getEnfantsM6()] : null;
        $filters['visites'] = null !== $this->getVisiteStatus() ? [$this->getVisiteStatus()] : null;
        if (null !== $this->getCriticiteScoreMin() || null !== $this->getCriticiteScoreMax()) {
            $filters['scores'] = [
                'on' => $this->getCriticiteScoreMin() ?? 0,
                'off' => $this->getCriticiteScoreMax() ?? 100,
            ];
        }
        if (null !== $this->getDateDepotDebut() && null !== $this->getDateDepotFin()) {
            $filters['dates'] = [
                'on' => $this->getDateDepotDebut(),
                'off' => $this->getDateDepotFin(),
            ];
        }
        $filters['tags'] = $this->getEtiquettes() ?? null;
        $filters['typeDeclarant'] = $this->getTypeDeclarant();
        $filters['page'] = $this->getPage() ?? 1;
        $filters['maxItemsPerPage'] = $this->getMaxItemPerPage();
        $filters['sortBy'] = $this->getSortBy() ?? 'reference';
        $filters['orderBy'] = $this->getOrderBy() ?? 'orderBy';

        return array_filter($filters);
    }
}
