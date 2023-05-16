<?php

namespace App\Service;

use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Service\Files\HeicToJpegConverter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadHandlerService
{
    public const MAX_FILESIZE = 10 * 1024 * 1024;

    private $file;

    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private HeicToJpegConverter $heicToJpegConverter
    ) {
        $this->file = null;
    }

    public function toTempFolder(UploadedFile $file): self|array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        if (empty($originalFilename) || !$file->isValid()) {
            return ['error' => 'Erreur lors du téléversement.', 'message' => 'Fichier vide', 'status' => 500];
        }
        $titre = $originalFilename.'.'.$file->guessExtension();
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }

        if (\in_array($file->getMimeType(), HeicToJpegConverter::HEIC_FORMAT)) {
            throw new UnsupportedFileFormatException($file->getMimeType());
        }

        try {
            $distantFolder = $this->parameterBag->get('bucket_tmp_dir');
            $fileResource = fopen($file->getPathname(), 'r');
            $this->fileStorage->writeStream($distantFolder.$newFilename, $fileResource);
            fclose($fileResource);
        } catch (FileException $e) {
            $this->logger->error($e->getMessage());

            return ['error' => 'Erreur lors du téléversement.', 'message' => $e->getMessage(), 'status' => 500];
        }
        if ($newFilename && '' !== $newFilename && $titre && '' !== $titre) {
            $this->file = ['file' => $newFilename, 'titre' => $titre];
        }

        return $this;
    }

    public function uploadFromFilename(string $filename, ?string $directory = null): ?string
    {
        $filename = null === $directory ? $filename : $directory.$filename;
        $this->logger->info($filename);
        $distantFolder = $this->parameterBag->get('bucket_tmp_dir');
        $tmpFilepath = $distantFolder.$filename;

        try {
            $tmpFilepath = $this->heicToJpegConverter->convert($tmpFilepath);

            $pathInfo = pathinfo($tmpFilepath);
            $newFilename = $pathInfo['filename'].'.'.$pathInfo['extension'];

            $this->fileStorage->move($tmpFilepath, $newFilename);

            return $newFilename;
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (\ImagickException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    /**
     * @throws MaxUploadSizeExceededException
     */
    public function uploadFromFile(UploadedFile $file, $newFilename): ?string
    {
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }
        try {
            $tmpFilepath = $file->getPathname();

            $newTmpFilepath = $this->heicToJpegConverter->convert($tmpFilepath, $newFilename);
            if ($newTmpFilepath !== $tmpFilepath) {
                $tmpFilepath = $newTmpFilepath;
                $pathInfo = pathinfo($tmpFilepath);
                $newFilename = $pathInfo['filename'].'.'.$pathInfo['extension'];
            }

            $fileResource = fopen($tmpFilepath, 'r');
            $this->fileStorage->writeStream($newFilename, $fileResource);
            fclose($fileResource);

            return $newFilename;
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (\ImagickException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function getTmpFilepath(string $filename): string
    {
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
        file_put_contents($tmpFilepath, file_get_contents($bucketFilepath));

        return $tmpFilepath;
    }

    public function createTmpFileFromBucket($from, $to): void
    {
        $resourceBucket = $this->fileStorage->read($from);
        $resourceFileSytem = fopen($to, 'w');
        fwrite($resourceFileSytem, $resourceBucket);
        fclose($resourceFileSytem);
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
