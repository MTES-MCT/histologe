<?php

namespace App\Dto\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    description: 'Payload pour créer un arrếté.',
    required: ['date', 'numero', 'type'],
)]
class ArreteRequest implements RequestInterface
{
    #[OA\Property(
        description: 'La date de l\'arrêté.',
        example: '2023-06-14',
    )]
    #[Groups(groups: ['Default', 'POST_ARRETE_REQUEST'])]
    #[Assert\NotBlank(groups: ['POST_ARRETE_REQUEST'])]
    #[Assert\Date(groups: ['POST_ARRETE_REQUEST'])]
    #[Assert\Callback([self::class, 'validatePastDate'], groups: ['POST_ARRETE_REQUEST'])]
    public ?string $date = null;

    #[OA\Property(
        description: 'Le numéro de dossier.',
        example: '2023/DD13/00664',
        nullable: true,
    )]
    #[Assert\NotBlank(groups: ['POST_ARRETE_REQUEST'])]
    #[Groups(groups: ['Default', 'POST_ARRETE_REQUEST'])]
    public ?string $numeroDossier = null;

    #[OA\Property(
        description: 'Le numéro de l\'arrêté.',
        example: '2023/DD13/00664',
    )]
    #[Groups(groups: ['Default', 'POST_ARRETE_REQUEST'])]
    #[Assert\NotBlank(groups: ['POST_ARRETE_REQUEST'])]
    public ?string $numero = null;

    #[OA\Property(
        description: 'Le type de l\'arrêté doit commencer par <strong>Arrêté L. suivi d\'un numéro d\'article de loi</strong>.',
        example: ['Arrêté L.511-11 - Suroccupation'],
    )]
    #[Groups(groups: ['Default', 'POST_ARRETE_REQUEST'])]
    #[Assert\NotBlank(groups: ['POST_ARRETE_REQUEST'])]
    #[Assert\Regex(
        pattern: "/^Arrêté L\.\d+/",
        message: "Le type doit commencer par 'Arrêté L.' suivi d'un numéro d'article de loi.",
        groups: ['POST_ARRETE_REQUEST']
    )]
    public ?string $type = null;

    #[OA\Property(
        description: 'La date de main-levée.',
        example: '2025-07-01',
        nullable: true,
    )]
    #[Groups(groups: ['Default', 'POST_ARRETE_REQUEST'])]
    #[Assert\Date]
    #[Assert\Callback([self::class, 'validatePastDate'], groups: ['POST_ARRETE_REQUEST'])]
    public ?string $mainLeveeDate = null;

    #[OA\Property(
        description: 'Le numéro de main-levée.',
        example: '2023-DD13-00172',
        nullable: true,
    )]
    #[Groups(groups: ['Default', 'POST_ARRETE_REQUEST'])]
    public ?string $mainLeveeNumero = null;

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

    #[Assert\Callback([self::class, 'validateMainLeveeAfterDate'])]
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
