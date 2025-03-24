<?php

namespace App\Dto\Api\Request;

use App\Validator\ValidFiles;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    description: 'Payload d\'une visite'
)]
#[Assert\Callback(callback: [self::class, 'checkFieldsWhenVisitePlannedOrConfirmed'], groups: ['POST_VISITE_REQUEST'])]
class VisiteRequest implements RequestInterface, RequestFileInterface
{
    #[Assert\NotBlank(groups: ['POST_VISITE_REQUEST'])]
    #[Assert\Date(groups: ['POST_VISITE_REQUEST'])]
    #[OA\Property(
        description: 'Date de la visite<br>Exemple : `2025-01-05`',
        format: 'date',
        example: '2025-01-05'
    )]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    public ?string $date = null;

    #[Assert\NotBlank(groups: ['POST_VISITE_REQUEST'])]
    #[Assert\Time(groups: ['POST_VISITE_REQUEST'], withSeconds: false)]
    #[OA\Property(description: 'Heure de la visite<br>Exemple : `10:00`', example: '10:00')]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    public ?string $time = null;

    #[Assert\Type('bool', groups: ['POST_VISITE_REQUEST'])]
    #[OA\Property(description: 'La visite a eu lieu', example: true)]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    public ?bool $visiteEffectuee = null;

    #[OA\Property(description: 'L\'occupant était présent', example: true)]
    #[Assert\Type('bool', groups: ['POST_VISITE_REQUEST'])]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    public ?bool $occupantPresent = null;

    #[OA\Property(description: 'Le propriétaire était présent', example: false)]
    #[Assert\Type('bool', groups: ['POST_VISITE_REQUEST'])]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    public ?bool $proprietairePresent = null;

    #[OA\Property(description: 'Notifier l\'usager', example: true)]
    #[Assert\Type('bool', groups: ['POST_VISITE_REQUEST'])]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
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
        example: ['LOGEMENT_DECENT', 'RESPONSABILITE_OCCUPANT_ASSURANTIEL'],
    )]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
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
        message: 'Veuillez choisir des valeurs valides. {{ choices }}',
        groups: ['POST_VISITE_REQUEST'],
    )]
    public array $concludeProcedure = [];

    #[Assert\Type('string', groups: ['POST_VISITE_REQUEST'])]
    #[OA\Property(description: 'Détails de la visite', example: '<p>Compte rendu de visite...</p>')]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    public ?string $details = null;

    #[OA\Property(
        description: 'Tableau contenant une liste d\'UUID des fichiers associés au signalement.',
        type: 'array',
        items: new OA\Items(type: 'string', format: 'uuid'),
        example: ['f47ac10b-58cc-4372-a567-0e02b2c3d479', '8d3c7db7-fc90-43f4-8066-7522f0e9b163']
    )]
    #[Groups(groups: ['Default', 'POST_VISITE_REQUEST'])]
    #[ValidFiles(groups: ['POST_VISITE_REQUEST'])]
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

    public static function checkFieldsWhenVisitePlannedOrConfirmed(
        self $object,
        ExecutionContextInterface $context,
    ): void {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $object->date);
        $today = new \DateTimeImmutable('today');

        $fieldsToCheck = [
            'visiteEffectuee' => $object->visiteEffectuee,
            'occupantPresent' => $object->occupantPresent,
            'proprietairePresent' => $object->proprietairePresent,
            'notifyUsager' => $object->notifyUsager,
            'concludeProcedure' => $object->concludeProcedure,
            'details' => $object->details,
        ];
        if ($dateTime && $dateTime > $today) {
            $fieldsToCheck['files'] = $object->files;
            foreach ($fieldsToCheck as $fieldName => $value) {
                if ($object->$fieldName !== null && $object->$fieldName !== []) {
                    $context->buildViolation(sprintf(
                        'Le champ "%s" ne peut être renseigné que si la visite a été effectuée.',
                        $fieldName))
                        ->atPath($fieldName)
                        ->addViolation();
                }
            }
        }

        if ($dateTime <= $today) {
            foreach ($fieldsToCheck as $fieldName => $value) {
                if (null === $value || [] === $value || '' === $value) {
                    $context->buildViolation(sprintf(
                        'Le champ "%s" est obligatoire pour une visite effectuée.',
                        $fieldName))
                        ->atPath($fieldName)
                        ->addViolation();
                }
            }
        }
    }
}
