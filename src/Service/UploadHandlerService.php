<?php

namespace App\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadHandlerService
{
    private $file;

    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
        private SluggerInterface $slugger,
        private Filesystem $filesystem,
        private LoggerInterface $logger
    ) {
        $this->file = null;
    }

    public function toTempFolder(UploadedFile $file): self|array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $titre = $originalFilename.'.'.$file->guessExtension();
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $this->parameterBag->get('uploads_tmp_dir'),
                $newFilename
            );
        } catch (FileException $e) {
            $this->logger->error($e->getMessage());

            return ['error' => 'Erreur lors du téléversement.', 'message' => $e->getMessage(), 'status' => 500];
        }
        if ($newFilename && '' !== $newFilename && $titre && '' !== $titre) {
            $this->file = ['file' => $newFilename, 'titre' => $titre];
        }

        return $this;
    }

    public function uploadFromFilename(string $filename): string
    {
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;

        try {
            $resourceFile = fopen($tmpFilepath, 'r');
            $this->fileStorage->writeStream($filename, $resourceFile);
            fclose($resourceFile);
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $filename;
    }

    public function uploadFromFile(UploadedFile $file, $newFilename): void
    {
        try {
            $fileResource = fopen($file->getPathname(), 'r');
            $this->fileStorage->writeStream($newFilename, $fileResource);
            fclose($fileResource);
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function setKey(string $key): ?array
    {
        $this->file['key'] = $key;

        return $this->file;
    }

    public function getFile(): array
    {
        return $this->file;
    }
}
