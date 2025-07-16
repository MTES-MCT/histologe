<?php

namespace App\Service\DashboardTabPanel;

readonly class TabDossier
{
    public const int MAX_ITEMS_LIST = 5;

    public function __construct(
        public ?string $profilDeclarant = null,
        public ?string $nomDeclarant = null,
        public ?string $prenomDeclarant = null,
        public ?string $reference = null,
        public ?string $adresse = null,
        public ?string $depotAt = null,
        public ?string $depotBy = null,
        public ?string $depotPartenaireBy = null,
        public ?string $valideAt = null,
        public ?string $validePartenaireBy = null,
        public ?string $clotureAt = null,
        public ?string $parc = null,
        public ?string $statut = null,
        public ?string $derniereAction = null,
        public ?string $derniereActionAt = null,
        public ?string $derniereActionTypeSuivi = null,
        public ?int $derniereActionPartenaireDaysAgo = null,
        public ?string $derniereActionPartenaireNom = null,
        public ?string $derniereActionPartenaireNomAgent = null,
        public ?string $derniereActionPartenairePrenomAgent = null,
        public ?string $actionDepuis = null,
        public ?string $messageAt = null,
        public ?int $messageDaysAgo = null,
        public ?string $messageSuiviByNom = null,
        public ?string $messageSuiviByPrenom = null,
        public ?string $messageByProfileDeclarant = null,
        public ?int $demandeFermetureUsagerDaysAgo = null,
        public ?string $demandeFermetureUsagerProfileDeclarant = null,
        public ?string $demandeFermetureUsagerAt = null,
        public ?int $nbRelanceDossier = null,
        public ?string $premiereRelanceDossierAt = null,
        public ?string $dernierSuiviPublicAt = null,
        public ?string $dernierTypeSuivi = null,
        public ?string $lien = null,
    ) {
    }
}
