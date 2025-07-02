<?php

namespace App\Service\DashboardTabPanel;

readonly class TabDossier
{
    public const int MAX_ITEMS_LIST = 2;

    public function __construct(
        public ?string $profilDeclarant = null,
        public ?string $reference = null,
        public ?string $adresse = null,
        public ?string $depotAt = null,
        public ?string $depotBy = null,
        public ?string $depotPartenaireBy = null,
        public ?string $valideAt = null,
        public ?string $validePartenaireBy = null,
        public ?string $parc = null,
        public ?string $statut = null,
        public ?string $derniereAction = null,
        public ?string $derniereActionAt = null,
        public ?string $actionDepuis = null,
        public ?string $lien = null,
    ) {
    }
}
