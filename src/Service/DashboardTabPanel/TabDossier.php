<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Enum\ProfileDeclarant;

readonly class TabDossier
{
    public const int MAX_ITEMS_LIST = 5;
    public const int MAX_ITEMS_LIST_LONG = 10;
    public const string CREATED_FROM_FORMULAIRE_USAGER = 'formulaire-usager';
    public const string CREATED_FROM_FORMULAIRE_PRO = 'formulaire-pro';

    public function __construct(
        public ?string $uuid = null,
        public ?ProfileDeclarant $profilDeclarant = null,
        public ?string $nomDeclarant = null,
        public ?string $prenomDeclarant = null,
        public ?string $reference = null,
        public ?string $adresse = null,
        public ?\DateTimeImmutable $depotAt = null,
        public ?string $depotBy = null,
        public ?string $depotPartenaireBy = null,
        public ?string $parc = null,
        public ?\DateTimeImmutable $valideAt = null,
        public ?string $validePartenaireBy = null,
        public ?\DateTimeImmutable $clotureAt = null,
        public ?string $statut = null,
        public ?string $derniereAction = null,
        public ?\DateTimeImmutable $derniereActionAt = null,
        public ?string $derniereActionTypeSuivi = null,
        public ?int $derniereActionPartenaireDaysAgo = null,
        public ?string $derniereActionPartenaireNom = null,
        public ?string $derniereActionPartenaireNomAgent = null,
        public ?string $derniereActionPartenairePrenomAgent = null,
        public ?string $actionDepuis = null,
        public ?\DateTimeImmutable $messageAt = null,
        public ?int $messageDaysAgo = null,
        public ?string $messageSuiviByNom = null,
        public ?string $messageSuiviByPrenom = null,
        public ?string $messageByProfileDeclarant = null,
        public ?int $demandeFermetureUsagerDaysAgo = null,
        public ?string $demandeFermetureUsagerProfileDeclarant = null,
        public ?\DateTimeImmutable $demandeFermetureUsagerAt = null,
        public ?int $nbRelanceDossier = null,
        public ?\DateTimeImmutable $premiereRelanceDossierAt = null,
        public ?\DateTimeImmutable $dernierSuiviPublicAt = null,
        public ?string $dernierTypeSuivi = null,
        public ?string $lien = null,
    ) {
    }
}
