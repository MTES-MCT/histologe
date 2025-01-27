<?php

namespace App\Dto\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    description: 'Requête pour lister les signalements avec pagination et filtres optionnels',
)]
readonly class SignalementListQueryParams implements RequestInterface
{
    public const string DEFAULT_LIMIT = '20';
    public const string DEFAULT_PAGE = '1';

    public function __construct(
        #[Assert\Type(
            type: 'numeric',
            message: 'La limite (limit) doit être un entier.'
        )]
        #[Assert\Range(notInRangeMessage: 'La limite (limit) doit être comprise entre 1 et 100.', min: 1, max: 100)]
        #[OA\Property(
            description: 'Nombre de signalements à retourner.',
            maximum: 100,
            minimum: 1,
            example: 20
        )]
        public string $limit = self::DEFAULT_LIMIT,
        #[Assert\Type(
            type: 'numeric',
            message: 'Le numéro de la page (page) doit être un entier.'
        )]
        #[Assert\Positive(message: 'Le numéro de la page (page) doit être un nombre positif.')]
        #[OA\Property(
            description: 'Numéro de la page de signalement à retourner.',
            minimum: 1,
            example: 1
        )]
        public string $page = self::DEFAULT_PAGE,

        #[Assert\Date(message: 'La date de début d\'affectation doit être valide. (exemple: 2025-01-01).')]
        #[Assert\LessThanOrEqual(propertyPath: 'dateAffectationFin', message: 'La date de début doit être antérieure ou égale à la date de fin.')]
        #[OA\Property(
            description: 'Date de début d\'affectation pour filtrer les signalements.',
            format: 'date',
            example: '2025-01-01',
            nullable: true
        )]
        public ?string $dateAffectationDebut = null,

        #[Assert\Date(message: 'La date de fin d\'affectation doit être valide. (exemple: 2025-01-31).')]
        #[OA\Property(
            description: 'Date de fin d\'affectation pour filtrer les signalements.',
            format: 'date',
            example: '2025-01-31',
            nullable: true
        )]
        public ?string $dateAffectationFin = null,
    ) {
    }
}
