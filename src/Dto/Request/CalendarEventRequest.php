<?php

namespace App\Dto\Request;

use App\Utils\UrlHelper;
use Symfony\Component\Validator\Constraints as Assert;

class CalendarEventRequest
{
    public const string COOKIE_NAME = 'list-addresses-history-filters';

    public const int MAX_LIST_PAGINATION = 25;

    /**
     * @param array<mixed> $arreteTypes
     */
    public function __construct(
        private readonly ?string $territoire = null,
        private readonly ?array $operateurs = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateFin = null,
        public ?string $uniquementMesDossiers = null,
    ) {
    }

        // TODO : en entrée : filtres territoire, opérateur, uniquement mes signalements (si pas SA), date de début, date de fin

    public function getTerritoire(): ?string
    {
        return $this->territoire;
    }

    /** @return array<mixed> */
    public function getOperateurs(): ?array
    {
        return $this->operateurs;
    }

    public function getDateDebut(): ?string
    {
        return $this->dateDebut;
    }

    public function getDateFin(): ?string
    {
        return $this->dateFin;
    }

    public function getUniquementMesDossiers(): ?string
    {
        return $this->uniquementMesDossiers;
    }

    /**
     * @return array<mixed>
     */
    public function getFilters(): array
    {
        $filters = [];
        $filters['territories'] = null !== $this->getTerritoire() ? [$this->getTerritoire()] : null;
        $filters['operateurs'] = $this->getOperateurs() ?? null;
        if (null !== $this->getDateDebut() || null !== $this->getDateFin()) {
            $filters['dates'] = [
                'on' => $this->getDateDebut(),
                'off' => $this->getDateFin(),
            ];
        }
        $filters['uniquementMesDossiers'] = $this->getUniquementMesDossiers() ?? null;

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
        return UrlHelper::arrayToQueryString($params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        return new self(
            territoire: $params['territoire'] ?? null,
            operateurs: $params['operateurs'] ?? null,
            dateDebut: $params['dateDebut'] ?? null,
            dateFin: $params['dateFin'] ?? null,
            uniquementMesDossiers: $params['uniquementMesDossiers'] ?? null,
        );
    }
}
