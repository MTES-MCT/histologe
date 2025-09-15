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
#[Assert\Callback(callback: [self::class, 'checkFieldsWhenVisitePlannedOrConfirmed'])]
#[Groups(groups: ['Default', 'false'])]
class VisiteRequest implements RequestInterface, RequestFileInterface
{
    #[Assert\NotBlank]
    #[Assert\Date]
    #[OA\Property(
        description: 'Date de la visite<br>Exemple : `2025-01-05`',
        format: 'date',
        example: '2025-01-05'
    )]
    public ?string $date = null;

    #[Assert\NotBlank]
    #[Assert\Time(withSeconds: false)]
    #[OA\Property(description: 'Heure de la visite<br>Exemple : `10:00`', example: '10:00')]
    public ?string $time = null;

    #[Assert\Type('bool')]
    #[OA\Property(description: 'La visite a eu lieu', example: true)]
    public ?bool $visiteEffectuee = null;

    #[OA\Property(description: 'L\'occupant était présent', example: true)]
    #[Assert\Type('bool')]
    public ?bool $occupantPresent = null;

    #[OA\Property(description: 'Le propriétaire était présent', example: false)]
    #[Assert\Type('bool')]
    public ?bool $proprietairePresent = null;

    #[OA\Property(description: 'Notifier l\'usager', example: true)]
    #[Assert\Type('bool')]
    public ?bool $notifyUsager = null;

    /** @var array<mixed> $concludeProcedure */
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
    )]
    public array $concludeProcedure = [];

    #[Assert\Type('string')]
    #[OA\Property(description: 'Informations sur la future visite', example: '<p>Merci de prévoir...</p>')]
    public ?string $commentBeforeVisite = null;

    #[Assert\Type('string')]
    #[OA\Property(description: 'Détails de la visite', example: '<p>Compte rendu de visite...</p>')]
    public ?string $details = null;

    /** @var array<mixed> $files */
    #[OA\Property(
        description: 'Tableau contenant une liste d\'UUID des fichiers associés au signalement.',
        type: 'array',
        items: new OA\Items(type: 'string', format: 'uuid'),
        example: ['f47ac10b-58cc-4372-a567-0e02b2c3d479', '8d3c7db7-fc90-43f4-8066-7522f0e9b163']
    )]
    #[ValidFiles]
    public array $files = [];

    #[OA\Property(
        description: 'Identifiant UUID du partenaire.',
        example: '342bf101-506d-4159-ba0c-c097f8cf12e7',
    )]
    #[Assert\Uuid(message: 'Veuillez fournir un UUID valide.')]
    public ?string $partenaireUuid = null;

    #[Ignore]
    public function getDescription(): ?string
    {
        return $this->details ?? null;
    }

    /**
     * @return array<mixed>
     */
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

            if (null !== $object->commentBeforeVisite && '' !== $object->commentBeforeVisite) {
                $context->buildViolation('Le champ "commentBeforeVisite" ne peut être renseigné que si la visite n\'a pas encore été effectuée.')
                    ->atPath('commentBeforeVisite')
                    ->addViolation();
            }
        }
    }
}
