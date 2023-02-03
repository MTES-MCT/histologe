<?php

namespace App\Service;

use App\Exception\MaxUploadSizeExceededException;
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
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }
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

    public function uploadFromFilename(string $filename, ?string $directory = null): string
    {
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;

        try {
            $fileResource = fopen($tmpFilepath, 'r');
            $filename = null === $directory ? $filename : $directory.$filename;
            $this->logger->info($filename);

            $convertResult = $this->convertToJpeg($fileResource, $filename);
            $fileResource = $convertResult['fileResource'];
            $filename = $convertResult['newFilename'];

            $this->fileStorage->writeStream($filename, $fileResource);
            fclose($fileResource);
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $filename;
    }

    public function uploadFromFile(UploadedFile $file, $newFilename): ?string
    {
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }
        try {
            $fileResource = fopen($file->getPathname(), 'r');

            $convertResult = $this->convertToJpeg($fileResource, $newFilename);
            $fileResource = $convertResult['fileResource'];
            $newFilename = $convertResult['newFilename'];

            $this->fileStorage->writeStream($newFilename, $fileResource);
            fclose($fileResource);

            return $newFilename;
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    private function convertToJpeg($fileResource, string $newFilename): array
    {
        $pathInfo = pathinfo($newFilename);
        if ('heic' === $pathInfo['extension']) {
            $newFilename = str_replace('.heic', '.jpg', $newFilename);
            $imageConvert = new \Imagick();
            $imageConvert->readImageFile($fileResource);
            $imageConvert->setImageFormat('jpeg');
            fclose($fileResource);

            $tempName = $this->parameterBag->get('uploads_tmp_dir').uniqid().'.jpg';
            $fileResourceWrite = fopen($tempName, 'w+');
            $imageConvert->writeImageFile($fileResourceWrite);
            fclose($fileResourceWrite);

            $fileResource = fopen($tempName, 'r');
        }

        return [
            'fileResource' => $fileResource,
            'newFilename' => $newFilename,
        ];
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
