<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\Api\PersonneType;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Personne',
    description: 'Détails concernant une personne associée, tels que l’occupant, le déclarant ou le propriétaire. <br>
    Contient des informations personnelles et leurs relations avec l’occupant.'
)]
class Personne
{
    public function __construct(
        #[OA\Property(
            description: 'Type de personne<br>
            <ul>
                <li>`OCCUPANT`</li>
                <li>`DECLARANT`</li>
                <li>`PROPRIETAIRE`</li>
            </ul>',
            nullable: true
        )]
        public ?PersonneType $personneType = null,
        #[OA\Property(
            description: 'Structure associée à la personne (dans le cas  d\'une organisation).',
            example: 'ADIL',
            nullable: true
        )]
        public ?string $structure = null,
        #[OA\Property(
            description: 'Lien entre le déclarant et l\'occupant.',
            type: 'string',
            example: 'Travailleur social',
            nullable: true
        )]
        public ?string $lienOccupant = null,

        #[OA\Property(
            description: 'Type si la personne est un bailleur.',
            type: 'string',
            example: 'Gestionnaire immobilier',
            nullable: true
        )]
        public ?string $precisionTypeSiBailleur = null,

        #[OA\Property(
            description: 'Indique si la personne est un travailleur social pour l\'occupant.',
            example: 'Oui',
            nullable: true
        )]
        public ?string $estTravailleurSocialPourOccupant = null,

        #[OA\Property(
            description: 'Civilité de la personne.<br>
            <ul>
                <li>mr</li>
                <li>mme</li>
            </ul>
',
            example: 'mr',
            nullable: true
        )]
        public ?string $civilite = null,

        #[OA\Property(
            description: 'Nom de la personne.',
            type: 'string',
            example: 'Dupont',
            nullable: true
        )]
        public ?string $nom = null,

        #[OA\Property(
            description: 'Prénom de la personne.',
            example: 'Jean',
            nullable: true
        )]
        public ?string $prenom = null,

        #[OA\Property(
            description: 'Adresse e-mail de la personne.',
            format: 'email',
            example: 'jean.dupont@email.com',
            nullable: true
        )]
        public ?string $email = null,
        #[OA\Property(
            description: 'Numéro de téléphone principal.',
            example: '+33612345678',
            nullable: true
        )]
        public ?string $telephone = null,

        #[OA\Property(
            description: 'Numéro de téléphone secondaire.',
            example: '+33789012345',
            nullable: true
        )]
        public ?string $telephoneSecondaire = null,
        #[OA\Property(
            description: 'Date de naissance de la personne.',
            format: 'date',
            example: '1980-05-15',
            nullable: true
        )]
        public ?string $dateNaissance = null,
        #[OA\Property(
            description: 'Revenu fiscal annuel de la personne.',
            example: '25000',
            nullable: true
        )]
        public ?string $revenuFiscal = null,
        #[OA\Property(
            description: 'Indique si la personne est bénéficiaire du RSA.',
            example: 'Oui',
            nullable: true
        )]
        public ?string $beneficiaireRsa = null,

        #[OA\Property(
            description: 'Indique si la personne est bénéficiaire du FSL.',
            example: 'Non',
            nullable: true
        )]
        public ?string $beneficiaireFsl = null,

        #[OA\Property(
            description: 'Indique si la personne est allocataire.',
            example: 'Non',
            nullable: true
        )]
        public ?string $allocataire = null,

        #[OA\Property(
            description: 'Type d\'allocataire `CAF` ou `MSA`.',
            example: 'CAF',
            nullable: true
        )]
        public ?string $typeAllocataire = null,

        #[OA\Property(
            description: 'Numéro d\'allocataire CAF ou MSA.',
            example: '1234567A',
            nullable: true
        )]
        public ?string $numAllocataire = null,

        #[OA\Property(
            description: 'Montant total de l\'allocation.',
            example: '500',
            nullable: true
        )]
        public ?string $montantAllocation = null,

        #[OA\Property(
            ref: '#/components/schemas/Adresse',
            description: 'Adresse de résidence de la personne.',
            nullable: true
        )]
        public ?Adresse $adresse = null,
    ) {
    }
}
