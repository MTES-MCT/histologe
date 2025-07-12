<?php

namespace App\Service\DashboardTabPanel;

use Symfony\Component\Validator\Constraints as Assert;

readonly class TabQueryParameters
{
    public function __construct(
        public ?int $territoireId = null,
        public ?string $communeCodePostal = null,
        /** @var array<int> */
        public ?array $partenairesId = null,
        #[Assert\Choice(['createdAt', 'closedAt', 'nbRelanceFeedbackUsager', 'nbDay', 'nomOccupant'])]
        public ?string $sortBy = null,
        #[Assert\Choice(['ASC', 'DESC', 'asc', 'desc'])]
        public ?string $orderBy = null,
    ) {
    }
}
