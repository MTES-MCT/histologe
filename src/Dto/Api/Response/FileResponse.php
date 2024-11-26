<?php

namespace App\Dto\Api\Response;

use App\Entity\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileResponse
{
    public string $uuid;
    public string $titre;
    public string $documentType;
    public string $url;

    public function __construct(
        File $file,
        UrlGeneratorInterface $urlGenerator,
    ) {
        $this->uuid = $file->getUuid();
        $this->titre = $file->getTitle();
        $this->documentType = $file->getDocumentType()->value;
        $this->url = $urlGenerator->generate('show_file', ['uuid' => $file->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
        // besoin d'exposer plus d'élements ?
    }
}
