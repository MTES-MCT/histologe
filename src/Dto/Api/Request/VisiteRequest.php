<?php

namespace App\Dto\Api\Request;

use App\Validator\ValidFiles;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    schema: 'VisiteRequest',
    description: 'Payload d\'une visite'
)]
class VisiteRequest implements RequestInterface, RequestFileInterface
{
    #[Assert\NotBlank]
    #[Assert\Date]
    #[Assert\Callback([self::class, 'validateDateIsPast'])]
    #[OA\Property(
        description: 'Date de la visite<br>Exemple : `2025-01-05`',
        format: 'date',
        example: '2025-01-05'
    )]
    public string $date;

    #[Assert\NotBlank]
    #[Assert\Time(withSeconds: false)]
    #[OA\Property(description: 'Heure de la visite<br>Exemple : `10:00`', example: '10:00')]
    public string $time;

    #[Assert\Type('bool')]
    #[OA\Property(description: 'L\'occupant était présent', example: true)]
    public bool $occupantPresent;

    #[OA\Property(description: 'Le propriétaire était présent', example: false)]
    #[Assert\Type('bool')]
    public ?bool $proprietairePresent = null;

    #[Assert\Type('bool')]
    #[OA\Property(description: 'Notifier l\'usager', example: true)]
    public ?bool $notifyUsager = null;

    #[OA\Property(
        description: 'Liste des procédures conclues<br>
        <ul>
            <li>`NON_DECENCE`</li>
            <li>`RSD`</li>
            <li>`INSALUBRITE`</li>
            <li>`MISE_EN_SECURITE_PERIL`</li>
            <li>`LOGEMENT_DECENT`</li>
            <li>`RESPONSABILITE_OCCUPANT_ASSURANTIEL`</li>
            <li>`AUTRE`</li>
        </ul>
        ',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['LOGEMENT_DECENT', 'RESPONSABILITE_OCCUPANT_ASSURANTIEL']
    )]
    #[Assert\NotBlank()]
    #[Assert\Choice(
        choices: [
            'NON_DECENCE',
            'RSD',
            'INSALUBRITE',
            'MISE_EN_SECURITE_PERIL',
            'LOGEMENT_DECENT',
            'RESPONSABILITE_OCCUPANT_ASSURANTIEL',
            'AUTRE',
        ],
        multiple: true,
        message: 'Veuillez choisir des valeurs valides. {{ choices }}'
    )]
    public array $concludeProcedure = [];

    #[Assert\Type('string')]
    #[OA\Property(description: 'Détails de la visite', example: '<p>Compte rendu de visite...</p>')]
    public ?string $details = null;

    #[OA\Property(
        description: 'Tableau contenant une liste d\'UUID des fichiers associés au signalement.',
        type: 'array',
        items: new OA\Items(type: 'string', format: 'uuid'),
        example: ['f47ac10b-58cc-4372-a567-0e02b2c3d479', '8d3c7db7-fc90-43f4-8066-7522f0e9b163']
    )]
    #[ValidFiles]
    public array $files = [];

    #[Ignore]
    public function getDescription(): ?string
    {
        return $this->details ?? null;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public static function validateDateIsPast(string $date, ExecutionContextInterface $context): void
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime || $dateTime > new \DateTimeImmutable('today')) {
            $context->buildViolation('La date de visite doit être antérieure à aujourd\'hui.')
                ->addViolation();
        }
    }
}
