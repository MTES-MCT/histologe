<?php

namespace App\Dto\Api\Request;

use App\Entity\File;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'FileRequest',
    description: 'Payload de téléversement de fichier.',
)]
class FilesUploadRequest implements RequestInterface
{
    /** @var array<mixed> $files */
    #[Assert\All([
        new Assert\Type(type: UploadedFile::class, message: 'Chaque élément doit être un fichier valide.'),
        new Assert\File(
            maxSize: '10M',
            mimeTypes: File::DOCUMENT_MIME_TYPES,
            maxSizeMessage: 'Le fichier ne doit pas dépasser 10 Mo.',
            mimeTypesMessage: 'Seuls les fichiers {{ types }} sont autorisés.'
        ),
    ], groups: ['single', 'multiple_documents'])]
    #[Assert\All([
        new Assert\Type(type: UploadedFile::class, message: 'Chaque élément doit être une image valide.'),
        new Assert\File(
            maxSize: '10M',
            mimeTypes: File::IMAGE_MIME_TYPES,
            maxSizeMessage: 'L\'image ne doit pas dépasser 10 Mo.',
            mimeTypesMessage: 'Seules les images {{ types }} sont autorisées.'
        ),
    ], groups: ['multiple_images'])]
    #[Assert\Count(
        min: 1,
        max: 5,
        minMessage: 'Veuillez téléverser au moins un fichier et l’envoyer sous la forme d’un tableau de fichiers (files[]).',
        maxMessage: 'Vous ne pouvez pas téléverser plus de {{ limit }} fichiers.',
        groups: ['multiple_documents', 'multiple_images']
    )]
    #[Assert\Count(
        min: 1,
        max: 1,
        exactMessage: 'Veuillez téléverser un seul fichier et l’envoyer sous la forme d’un tableau de fichiers (files[]).',
        minMessage: 'Veuillez téléverser un seul fichier.',
        maxMessage: 'Vous ne pouvez téléverser qu\'un seul fichier.',
        groups: ['single']
    )]
    #[OA\Property(
        description: 'Liste des fichiers téléversés',
        type: 'array',
        items: new OA\Items(type: 'string', format: 'binary')
    )]
    public array $files = [];

    #[OA\Property(
        description: 'Identifiant UUID du partenaire.',
        example: '342bf101-506d-4159-ba0c-c097f8cf12e7',
    )]
    #[Assert\Uuid(message: 'Veuillez fournir un UUID valide.')]
    public ?string $partenaireUuid = null;
}
