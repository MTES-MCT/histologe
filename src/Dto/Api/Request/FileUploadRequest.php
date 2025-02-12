<?php

namespace App\Dto\Api\Request;

use App\Entity\File;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class FileUploadRequest implements RequestInterface
{
    public function __construct(
        #[Assert\File(
            maxSize: '10M',
            mimeTypes: File::DOCUMENT_MIME_TYPES,
            maxSizeMessage: 'Le fichier ne doit pas dépasser 10 Mo.',
            mimeTypesMessage: 'Seuls les fichiers {{mimeTypes}} sont autorisés.'
        )]
        #[OA\Property(description: 'Fichier téléversé', type: 'string', format: 'binary')]
        public mixed $file = null,
    ) {
    }
}
