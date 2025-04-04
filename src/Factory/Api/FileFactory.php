<?php

namespace App\Factory\Api;

use App\Controller\FileController;
use App\Dto\Api\Model\File;
use App\Entity\File as FileEntity;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class FileFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UrlSignerInterface $urlSigner,
    ) {
    }

    public function createFrom(FileEntity $fileEntity): File
    {
        $file = new File();
        $file->uuid = $fileEntity->getUuid();
        $file->titre = $fileEntity->getTitle();
        $file->documentType = $fileEntity->getDocumentType()->value;
        $file->description = $fileEntity->getDescription();
        $url = $this->urlGenerator->generate(
            'show_file',
            ['uuid' => $fileEntity->getUuid()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $file->url = $this->urlSigner->sign($url, FileController::SIGNATURE_VALIDITY_DURATION);

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
