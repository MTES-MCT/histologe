<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\Signalement;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Repository\FileRepository;
use App\Service\Files\FilenameGenerator;
use App\Service\Files\HeicToJpegConverter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHandlerService
{
    public const MAX_FILESIZE = 10 * 1024 * 1024;

    private array $file = [];

    public function __construct(
        private readonly FilesystemOperator $fileStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
        private readonly HeicToJpegConverter $heicToJpegConverter,
        private readonly FilenameGenerator $filenameGenerator,
    ) {
    }

    /**
     * @throws MaxUploadSizeExceededException
     * @throws FilesystemException
     * @throws UnsupportedFileFormatException
     */
    public function toTempFolder(UploadedFile $file): self|array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        if (empty($originalFilename) || !$file->isValid()) {
            return ['error' => 'Erreur lors du téléversement.', 'message' => 'Fichier vide', 'status' => 500];
        }
        $newFilename = $this->filenameGenerator->generate($file);
        $titre = $this->filenameGenerator->getTitle();
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

            return [
                'error' => 'Erreur lors du téléversement.',
                'message' => $e->getMessage(),
                'status' => 500,
            ];
        }

        if (!empty($newFilename) && !empty($titre)) {
            $filePath = $distantFolder.$newFilename;
            $this->file = ['file' => $newFilename, 'filePath' => $filePath, 'titre' => $titre];
        }

        return $this;
    }

    private function moveFilePath(string $filePath): ?string
    {
        try {
            $pathInfo = pathinfo($filePath);
            $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
            $newFilename = $pathInfo['filename'].$ext;

            if ($this->fileStorage->fileExists($newFilename)) {
                return $newFilename;
            }

            if ($this->fileStorage->fileExists($filePath)) {
                $this->fileStorage->move($filePath, $newFilename);

                return $newFilename;
            }
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (\ImagickException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function moveFromBucketTempFolder(string $filename, ?string $directory = null): ?string
    {
        $filename = null === $directory ? $filename : $directory.$filename;
        $this->logger->info($filename);
        $distantFolder = $this->parameterBag->get('bucket_tmp_dir');
        $tmpFilepath = $distantFolder.$filename;

        $this->movePhotoVariants($filename);

        return $this->moveFilePath($tmpFilepath);
    }

    private function movePhotoVariants(string $filename): void
    {
        $variantNames = ImageManipulationHandler::getVariantNames($filename);
        $distantFolder = $this->parameterBag->get('bucket_tmp_dir');
        $this->moveFilePath($distantFolder.$variantNames[ImageManipulationHandler::SUFFIX_RESIZE]);
        $this->moveFilePath($distantFolder.$variantNames[ImageManipulationHandler::SUFFIX_THUMB]);
    }

    public function deleteSignalementFile(Signalement $signalement, string $type, string $filename, FileRepository $fileRepository): bool
    {
        $fileType = 'documents' === $type ? File::FILE_TYPE_DOCUMENT : File::FILE_TYPE_PHOTO;

        $fileCollection = $signalement->getFiles()->filter(
            function (File $file) use ($fileType, $filename) {
                return $fileType === $file->getFileType()
                    && $filename === $file->getFilename();
            }
        );

        if (!$fileCollection->isEmpty()) {
            $file = $fileCollection->current();
            $this->deleteFileInBucket($file);
            $fileRepository->remove($file, true);

            return true;
        }

        return false;
    }

    public function deleteFileInBucket(File $file): void
    {
        if ($this->fileStorage->fileExists($file->getFilename())) {
            $this->fileStorage->delete($file->getFilename());
        }
        $variantNames = ImageManipulationHandler::getVariantNames($file->getFilename());
        if ($this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])) {
            $this->fileStorage->delete($variantNames[ImageManipulationHandler::SUFFIX_RESIZE]);
        }
        if ($this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
            $this->fileStorage->delete($variantNames[ImageManipulationHandler::SUFFIX_THUMB]);
        }
    }

    public function uploadFromFilename(string $filename): ?string
    {
        $this->logger->info($filename);
        $localTmpFolder = $this->parameterBag->get('uploads_tmp_dir');
        $tmpFilepath = $localTmpFolder.$filename;

        try {
            $pathInfo = pathinfo($tmpFilepath);
            $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
            $newFilename = $pathInfo['filename'].$ext;

            $fileResource = fopen($tmpFilepath, 'r');
            $this->fileStorage->writeStream($newFilename, $fileResource);

            return $newFilename;
        } catch (FilesystemException $exception) {
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
                $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
                $newFilename = $pathInfo['filename'].$ext;
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

    public function getTmpFilepath(string $filename): ?string
    {
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
        try {
            file_put_contents($tmpFilepath, file_get_contents($bucketFilepath));

            return $tmpFilepath;
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function createTmpFileFromBucket($from, $to): void
    {
        $resourceBucket = $this->fileStorage->read($from);
        $resourceFileSyStem = fopen($to, 'w');
        fwrite($resourceFileSyStem, $resourceBucket);
        fclose($resourceFileSyStem);
    }

    public function getFileSize(string $filename): ?int
    {
        try {
            $variantNames = ImageManipulationHandler::getVariantNames($filename);
            $fileResize = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE];
            if ($this->fileStorage->fileExists($fileResize)) {
                return $this->fileStorage->fileSize($fileResize);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function hasVariants(string $filename): bool
    {
        try {
            $variantNames = ImageManipulationHandler::getVariantNames($filename);
            if ($this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE]) && $this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
                return true;
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return false;
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
