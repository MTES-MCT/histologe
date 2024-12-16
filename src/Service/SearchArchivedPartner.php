<?php

namespace App\Service;

use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;
use Symfony\Component\Validator\Constraints as Assert;

class SearchArchivedPartner
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?string $queryArchivedPartner = null;
    private ?string $territory = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isSuperAdmin() && 1 === count($user->getPartnersTerritories())) {
            $this->territory = (string) $user->getFirstTerritory()->getId();
        }
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPage(): int
    {
        if ($this->page < 1) {
            return 1;
        }

        return $this->page;
    }

    public function setPage(?int $page): void
    {
        $this->page = $page;
    }

    public function getQueryArchivedPartner(): ?string
    {
        return $this->queryArchivedPartner;
    }

    public function setQueryArchivedPartner(?string $queryArchivedPartner): void
    {
        $this->queryArchivedPartner = $queryArchivedPartner;
    }

    public function getTerritory(): ?string
    {
        return $this->territory;
    }

    public function setTerritory(?string $territory): void
    {
        $this->territory = $territory;
    }

    public function getUrlParams(): array
    {
        $params = $this->getUrlParamsBase();
        if (isset($params['territory']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territory']);
        }

        return $params;
    }
}
