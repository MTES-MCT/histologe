<?php

namespace App\Service;

use App\Entity\File;
use App\Exception\File\EmptyFileException;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Repository\FileRepository;
use App\Service\Files\FilenameGenerator;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHandlerService
{
    public const int MAX_FILESIZE = 10 * 1024 * 1024;

    /**
     * @var array<mixed>
     */
    private array $file = [];

    public function __construct(
        private readonly FilesystemOperator $fileStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
        private readonly FilenameGenerator $filenameGenerator,
        private readonly FileRepository $fileRepository,
    ) {
    }

    /**
     * @return array<mixed>
     *
     * @throws MaxUploadSizeExceededException
     * @throws EmptyFileException
     * @throws FilesystemException
     * @throws UnsupportedFileFormatException
     */
    public function toTempFolder(UploadedFile $file, ?string $fileType = null): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        if (empty($originalFilename) || !$file->isValid()) {
            return ['error' => 'Erreur lors du téléversement.', 'message' => 'Fichier vide', 'status' => 500];
        }
        $newFilename = $this->filenameGenerator->generate($file);
        $titre = $this->filenameGenerator->getTitle();

        if ($this->isFileEmpty($file)) {
            throw new EmptyFileException();
        }
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }
        if (!self::isAcceptedDocumentFormat($file, $fileType)) {
            throw new UnsupportedFileFormatException($file, $fileType);
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
        if (!empty($newFilename) && !empty($titre)) {
            $filePath = $distantFolder.$newFilename;
            $this->file = ['file' => $newFilename, 'filePath' => $filePath, 'titre' => $titre];
        }

        return $this->file;
    }

    public static function isAcceptedDocumentFormat(UploadedFile $file, ?string $fileType = null): bool
    {
        if ('photo' === $fileType) {
            return \in_array($file->getMimeType(), File::IMAGE_MIME_TYPES);
        }

        return \in_array($file->getMimeType(), File::DOCUMENT_MIME_TYPES);
    }

    public static function getAcceptedExtensions(?string $type = null): string
    {
        if ('resizable' === $type) {
            $extensions = array_map('strtoupper', array_diff(File::IMAGE_EXTENSION, ['pdf']));
        } elseif ('photo' === $type) {
            $extensions = array_map('strtoupper', File::IMAGE_EXTENSION);
        } else {
            $extensions = array_map('strtoupper', File::DOCUMENT_EXTENSION);
        }

        $allButLast = \array_slice($extensions, 0, -1);
        $last = end($extensions);
        $all = implode(', ', $allButLast).' ou '.$last;

        return $all;
    }

    /**
     * @throws EmptyFileException
     * @throws MaxUploadSizeExceededException
     */
    public function isFileSizeOk(
        UploadedFile $file,
    ): bool {
        if ($this->isFileEmpty($file)) {
            throw new EmptyFileException();
        }
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }

        return true;
    }

    public function moveFilePath(string $filePath): ?string
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

    public function movePhotoVariants(string $filename): void
    {
        $variantNames = ImageManipulationHandler::getVariantNames($filename);
        $distantFolder = $this->parameterBag->get('bucket_tmp_dir');
        $this->moveFilePath($distantFolder.$variantNames[ImageManipulationHandler::SUFFIX_RESIZE]);
        $this->moveFilePath($distantFolder.$variantNames[ImageManipulationHandler::SUFFIX_THUMB]);
    }

    public function copyToNewFilename(string $filename, string $newFilename): ?string
    {
        try {
            if ($this->fileStorage->fileExists($filename) && !$this->fileStorage->fileExists($newFilename)) {
                $this->fileStorage->copy($filename, $newFilename);

                return $newFilename;
            }
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function copyPhotoVariantsToNewFilename(string $filename, string $newFilename): void
    {
        $variantNames = ImageManipulationHandler::getVariantNames($filename);
        $variantNewNames = ImageManipulationHandler::getVariantNames($newFilename);
        $this->copyToNewFilename($variantNames[ImageManipulationHandler::SUFFIX_RESIZE], $variantNewNames[ImageManipulationHandler::SUFFIX_RESIZE]);
        $this->copyToNewFilename($variantNames[ImageManipulationHandler::SUFFIX_THUMB], $variantNewNames[ImageManipulationHandler::SUFFIX_THUMB]);
    }

    public function deleteIfExpiredFile(File $file): bool
    {
        if ((new \DateTime())->getTimestamp() - $file->getCreatedAt()->getTimestamp() > 3600) {
            $this->deleteFile($file);

            return true;
        }

        return false;
    }

    public function deleteFile(File $file): bool
    {
        try {
            $this->deleteFileInBucket($file);
            $this->fileRepository->remove($file, true);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteFileInBucket(File $file): void
    {
        $this->deleteFileInBucketFromFilename($file->getFilename());
    }

    public function deleteFileInBucketFromFilename(string $filename): void
    {
        $this->deleteSingleFile($filename);
        $variantNames = ImageManipulationHandler::getVariantNames($filename);
        $this->deleteSingleFile($variantNames[ImageManipulationHandler::SUFFIX_RESIZE]);
        $this->deleteSingleFile($variantNames[ImageManipulationHandler::SUFFIX_THUMB]);
    }

    public function deleteSingleFile(string $filename): void
    {
        if ($this->fileStorage->fileExists($filename)) {
            $this->fileStorage->delete($filename);
        }
    }

    public function uploadFromFilename(string $filename, ?string $fromFolder = null): ?string
    {
        $this->logger->info($filename);
        $fromFolder = $fromFolder ?? $this->parameterBag->get('uploads_tmp_dir');
        $tmpFilepath = $fromFolder.$filename;

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
     * @throws EmptyFileException
     * @throws MaxUploadSizeExceededException
     * @throws UnsupportedFileFormatException
     */
    public function uploadFromFile(
        UploadedFile $file,
        string $newFilename,
    ): ?string {
        if ($this->isFileEmpty($file)) {
            throw new EmptyFileException();
        }
        if ($file->getSize() > self::MAX_FILESIZE) {
            throw new MaxUploadSizeExceededException(self::MAX_FILESIZE);
        }
        if (!self::isAcceptedDocumentFormat($file)) {
            throw new UnsupportedFileFormatException($file);
        }
        try {
            $tmpFilepath = $file->getPathname();

            $fileResource = fopen($tmpFilepath, 'r');
            $this->fileStorage->writeStream($newFilename, $fileResource);
            fclose($fileResource);

            return $newFilename;
        } catch (FilesystemException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function getTmpFilepath(string $filename): ?string
    {
        try {
            $variantNames = ImageManipulationHandler::getVariantNames($filename);
            $fileResize = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE];
            if ($this->fileStorage->fileExists($fileResize)) {
                $filename = $fileResize;
            }
            $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
            $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
            file_put_contents($tmpFilepath, file_get_contents($bucketFilepath));

            return $tmpFilepath;
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function createTmpFileFromBucket(string $from, string $to): void
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

    /**
     * @return array<mixed>
     */
    public function getFile(): array
    {
        return $this->file;
    }

    private function isFileEmpty(UploadedFile $file): bool
    {
        if (0 === $file->getSize() || 'application/x-empty' === $file->getMimeType()) {
            return true;
        }

        return false;
    }
}
