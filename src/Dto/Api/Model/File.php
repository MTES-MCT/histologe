<?php

namespace App\Dto\Api\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'File',
    description: 'Représentation d\'un fichier.'
)]
class File
{
    public string $titre;
    public string $documentType;
    public string $url;
}
