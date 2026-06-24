<?php

namespace App\Dto\Request\Signalement;

use App\Utils\UrlHelper;
use Symfony\Component\Validator\Constraints as Assert;

class AddressesHistorySearchQuery
{
    public const string COOKIE_NAME = 'list-addresses-history-filters';

    public const int MAX_LIST_PAGINATION = 25;

    /**
     * @param array<mixed> $communes
     * @param array<mixed> $zones
     * @param array<mixed> $typesArretes
     */
    public function __construct(
        private readonly ?string $territoire = null,
        private readonly ?string $adresse = null,
        private readonly ?array $communes = null,
        private readonly ?string $bailleurOuSyndic = null,
        private readonly ?array $zones = null,
        #[Assert\Choice(choices: ['privee', 'public', 'non_renseigne'], message: 'Nature du parc invalide')]
        private readonly ?string $natureParc = null,
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Dossiers multiples invalide')]
        private readonly ?string $dossiersMultiples = null,
        private readonly ?array $typesArretes = null,
        private readonly ?int $page = 1,
        /*
        #[Assert\Choice(choices: ['reference', 'nomOccupant', 'lastSuiviAt', 'villeOccupant', 'createdAt'], message: 'Champ de tri invalide')]
        private readonly string $sortBy = 'reference',
        #[Assert\Choice(choices: ['ASC', 'DESC', 'asc', 'desc'], message: 'Direction de tri invalide')]
        private readonly string $direction = 'DESC',
        */
    ) {
    }

    public function getTerritoire(): ?string
    {
        return $this->territoire;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    /** @return array<mixed> */
    public function getCommunes(): ?array
    {
        return $this->communes;
    }

    public function getBailleurOuSyndic(): ?string
    {
        return $this->bailleurOuSyndic;
    }

    /** @return array<mixed> */
    public function getZones(): ?array
    {
        return $this->zones;
    }

    public function getNatureParc(): ?string
    {
        return $this->natureParc;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getDossiersMultiples(): ?string
    {
        return $this->dossiersMultiples;
    }

    /** @return array<mixed> */
    public function getTypesArretes(): ?array
    {
        return $this->typesArretes;
    }

    /*
    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
        */

    /**
     * @return array<mixed>
     */
    public function getFilters(): array
    {
        $filters = [];
        $filters['territories'] = null !== $this->getTerritoire() ? [$this->getTerritoire()] : null;
        $filters['adresse'] = $this->getAdresse() ?? null;
        $filters['cities'] = $this->getCommunes() ?? null;
        $filters['bailleurOrSyndic'] = $this->getBailleurOuSyndic() ?? null;
        $filters['zones'] = $this->getZones() ?? null;
        $filters['housetypes'] = match ($this->getNatureParc()) {
            'public' => [1],
            'privee' => [0],
            'non_renseigne' => ['non_renseigne'],
            default => null,
        };
        $filters['dossiersMultiples'] = $this->getDossiersMultiples();
        $filters['typesArretes'] = $this->getTypesArretes() ?? null;
        $filters['page'] = $this->getPage() ?? 1;
        $filters['maxItemsPerPage'] = self::MAX_LIST_PAGINATION;
        /*
        $filters['sortBy'] = $this->getSortBy();
        $filters['orderBy'] = $this->getDirection();
        */

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

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        return new self(
            territoire: $params['territoire'] ?? null,
            adresse: $params['adresse'] ?? null,
            communes: isset($params['communes']) && is_array($params['communes']) ? $params['communes'] : null,
            bailleurOuSyndic: $params['bailleurOuSyndic'] ?? null,
            zones: isset($params['zones']) && is_array($params['zones']) ? $params['zones'] : null,
            natureParc: $params['natureParc'] ?? null,
            dossiersMultiples: $params['dossiersMultiples'] ?? null,
            typesArretes: $params['typesArretes'] ?? null,
            page: isset($params['page']) ? (int) $params['page'] : 1,
            /*
            sortBy: $params['sortBy'] ?? 'reference',
            direction: $params['direction'] ?? 'DESC',
            */
        );
    }
}
