<?php

namespace App\Dto\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    description: 'Payload pour créer un arrêté.',
    required: ['date', 'numero', 'type', 'numeroDossier'],
)]
#[Groups(groups: ['Default', 'false'])]
class ArreteRequest implements RequestInterface
{
    #[OA\Property(
        description: 'La date de l\'arrêté.',
        example: '2023-06-14',
    )]
    #[Assert\NotBlank]
    #[Assert\Date]
    #[Assert\Callback(callback: [self::class, 'validatePastDate'])]
    public ?string $date = null;

    #[OA\Property(
        description: 'Le numéro de dossier.',
        example: '2023/DD13/00664',
    )]
    #[Assert\NotBlank]
    public ?string $numeroDossier = null;

    #[OA\Property(
        description: 'Le numéro de l\'arrêté.',
        example: '2023/DD13/00664',
    )]
    #[Assert\NotBlank]
    public ?string $numero = null;

    #[OA\Property(
        description: 'Le type de l\'arrêté.',
        enum: [
            'Mise en sécurité procédure ordinaire',
            'Mise en sécurité procédure urgente',
            'Mise en sécurité modificatif',
            'Impropre',
            'Ordinaire irrémédiable',
            'Ordinaire remédiable',
            'Suroccupation',
            'Usage non approprié',
            'Insalubrité',
            'Insalubrité & Saturnisme',
            'Saturnisme',
            'Arrêté L1331-26',
            'Arrêté L.1311-4',
            'Arrêté Letchimy : article 10',
        ],
        example: ['Suroccupation'],
    )]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: [
            'Mise en sécurité procédure ordinaire',
            'Mise en sécurité procédure urgente',
            'Mise en sécurité modificatif',
            'Impropre',
            'Ordinaire irrémédiable',
            'Ordinaire remédiable',
            'Suroccupation',
            'Usage non approprié',
            'Insalubrité',
            'Insalubrité & Saturnisme',
            'Saturnisme',
            'Arrêté L1331-26',
            'Arrêté L.1311-4',
            'Arrêté Letchimy : article 10',
        ],
        message: 'Cette valeur doit être l\'un des choix suivants : {{ choices }}'
    )]
    // TODO basculer en Enum ArreteType
    public ?string $type = null;

    #[OA\Property(
        description: 'La date de main-levée.',
        example: '2025-07-01',
        nullable: true,
    )]
    #[Assert\Date]
    #[Assert\Callback(callback: [self::class, 'validatePastDate'])]
    public ?string $mainLeveeDate = null;

    #[OA\Property(
        description: 'Le numéro de main-levée.',
        example: '2023-DD13-00172',
        nullable: true,
    )]
    public ?string $mainLeveeNumero = null;

    #[OA\Property(
        description: 'Identifiant UUID du partenaire.',
        example: '342bf101-506d-4159-ba0c-c097f8cf12e7',
    )]
    #[Assert\Uuid(message: 'Veuillez fournir un UUID valide.')]
    public ?string $partenaireUuid = null;

    public static function validatePastDate(?string $dateValue, ExecutionContextInterface $context): void
    {
        if (null === $dateValue) {
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateValue);
        if ($date > new \DateTimeImmutable()) {
            $context
                ->buildViolation('Cette valeur doit être une date passée.')
                ->addViolation();
        }
    }

    #[Assert\Callback]
    public function validateMainLeveeAfterDate(ExecutionContextInterface $context): void
    {
        if (null === $this->date || null === $this->mainLeveeDate) {
            return;
        }

        $dateArrete = \DateTimeImmutable::createFromFormat('Y-m-d', $this->date);
        $dateMainLevee = \DateTimeImmutable::createFromFormat('Y-m-d', $this->mainLeveeDate);

        if (!$dateArrete || !$dateMainLevee) {
            return;
        }

        if ($dateMainLevee <= $dateArrete) {
            $context->buildViolation('La date de main-levée doit être postérieure à la date de l\'arrêté.')
                ->atPath('mainLeveeDate')
                ->addViolation();
        }
    }
}
