<?php

namespace App\Factory\Api;

use App\Dto\Api\Model\File;
use App\Entity\File as FileEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class FileFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFrom(FileEntity $fileEntity): File
    {
        $file = new File();
        $file->uuid = $fileEntity->getUuid();
        $file->titre = $fileEntity->getTitle();
        $file->documentType = $fileEntity->getDocumentType()->value;
        $file->url = $this->urlGenerator->generate(
            'show_file',
            ['uuid' => $fileEntity->getUuid()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $file;
    }

    public function createFromArray(array $files): array
    {
        $fileList = [];
        foreach ($files as $file) {
            $fileList[] = $this->createFrom($file);
        }

        return $fileList;
    }
}
