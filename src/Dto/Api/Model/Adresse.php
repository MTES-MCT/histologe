<?php

namespace App\Dto\Api\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Adresse',
    description: 'Représentation d\'une adresse complète. <br>
    L\'adresse du signalement correspond au logement concerné par le signalement (celle de l\'occupant). <br>
    Les autres types de personnes (comme le déclarant ou le propriétaire) auront leur adresse spécifiée dans l\'objet personne.',
)]
class Adresse
{
    public function __construct(
        #[OA\Property(
            description: 'Ligne principale de l\'adresse',
            example: '123 Rue de Paris'
        )]
        public ?string $adresse = null,
        #[OA\Property(
            description: 'Code postal',
            example: '75000'
        )]
        public ?string $codePostal = null,
        #[OA\Property(
            description: 'Ville',
            example: 'Paris'
        )]
        public ?string $ville = null,
        #[OA\Property(
            description: 'Numéro d\'étage',
            example: '3'
        )]
        public ?string $etage = null,
        #[OA\Property(
            description: 'Escalier spécifié',
            example: 'B'
        )]
        public ?string $escalier = null,
        #[OA\Property(
            description: 'Numéro d\'appartement',
            example: '34A'
        )]
        public ?string $numAppart = null,
        #[OA\Property(
            description: 'Code INSEE de la ville',
            example: '75101'
        )]
        public ?string $codeInsee = null,
        #[OA\Property(
            description: 'Latitude géographique',
            type: 'number',
            format: 'float',
            example: 48.8566
        )]
        public ?float $latitude = null,
        #[OA\Property(
            description: 'Longitude géographique',
            type: 'number',
            format: 'float',
            example: 2.3522
        )]
        public ?float $longitude = null,
        #[OA\Property(
            description: 'Autre adresse (si applicable)',
            type: 'string',
            example: '45B Rue des Lilas'
        )]
        public ?string $adresseAutre = null,
        #[OA\Property(
            description: 'Identifiant RNB',
            example: 'RNB123456'
        )]
        public ?string $rnbId = null,
        #[OA\Property(
            description: 'Clé BAN de l\'adresse',
            example: '763f8c4b'
        )]
        public ?string $cleBanAdresse = null,
    ) {
    }
}
