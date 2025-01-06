<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\Api\PersonneType;

class Personne
{
    public function __construct(
        public ?PersonneType $personneType = null,
        public ?string $structure = null,
        public ?string $lienOccupant = null,
        public ?string $precisionTypeSiBailleur = null,
        public ?string $estTravailleurSocialPourOccupant = null,
        public ?string $civilite = null,
        public ?string $nom = null,
        public ?string $prenom = null,
        public ?string $email = null,
        public ?string $telephone = null,
        public ?string $telephoneSecondaire = null,
        public ?string $dateNaissance = null,
        public ?string $revenuFiscal = null,
        public ?string $beneficiaireRsa = null,
        public ?string $beneficiaireFsl = null,
        public ?string $allocataire = null,
        public ?string $typeAllocataire = null,
        public ?string $numAllocataire = null,
        public ?string $montantAllocation = null,
        public ?Adresse $adresse = null,
    ) {
    }
}
