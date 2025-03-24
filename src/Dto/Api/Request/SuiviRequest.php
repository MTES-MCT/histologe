<?php

namespace App\Dto\Api\Request;

use App\Validator\SanitizedLength;
use App\Validator\ValidFiles;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

#[OA\Schema(
    description: 'Payload pour créer un suivi.',
    required: ['description'],
)]
class SuiviRequest implements RequestInterface, RequestFileInterface
{
    #[OA\Property(
        description: 'Un message de 10 caractères minimum est obligatoire.',
        example: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
    )]
    #[Groups(groups: ['Default', 'POST_SUIVI_REQUEST'])]
    #[SanitizedLength(min: 10, groups: ['POST_SUIVI_REQUEST'])]
    public ?string $message = null;
    public ?string $description = null;

    #[OA\Property(
        description: 'Permet d\'indiquer si l\'usager doit être notifié.',
        default: false,
        example: true,
    )]
    #[Groups(groups: ['Default', 'POST_SUIVI_REQUEST'])]
    public bool $notifyUsager = false;

    #[OA\Property(
        description: 'Tableau contenant une liste d\'UUID des fichiers associés au signalement.',
        type: 'array',
        items: new OA\Items(type: 'string', format: 'uuid'),
        example: ['f47ac10b-58cc-4372-a567-0e02b2c3d479', '8d3c7db7-fc90-43f4-8066-7522f0e9b163']
    )]
    #[Groups(groups: ['Default', 'POST_SUIVI_REQUEST'])]
    #[ValidFiles(groups: ['POST_SUIVI_REQUEST'])]
    public array $files = [];

    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    public function getFiles(): array
    {
        return $this->files;
    }
}
