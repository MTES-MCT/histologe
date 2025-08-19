<?php

namespace App\Service\DashboardTabPanel;

use Symfony\Component\Validator\Constraints as Assert;

class TabQueryParameters
{
    public function __construct(
        public ?int $territoireId = null,
        public ?string $communeCodePostal = null,
        #[Assert\Choice([TabDossier::CREATED_FROM_FORMULAIRE_USAGER, TabDossier::CREATED_FROM_FORMULAIRE_PRO])]
        public ?string $createdFrom = null,
        /** @var array<int|string> */
        public ?array $partenairesId = null,
        #[Assert\Choice(['createdAt', 'closedAt', 'nbRelanceFeedbackUsager', 'nbDay', 'nomOccupant', 'demandeFermetureUsagerAt'])]
        public ?string $sortBy = null,
        #[Assert\Choice(['ASC', 'DESC', 'asc', 'desc'])]
        public ?string $orderBy = null,
        public ?string $mesDossiersMessagesUsagers = null,
        public ?string $mesDossiersAverifier = null,
    ) {
    }
}
