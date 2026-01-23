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
            $this->logger->info('Téléversement du fichier vers le dossier temporaire du bucket.', [
                'original_filename' => $file->getClientOriginalName(),
                'new_filename' => $newFilename,
                'file_type' => $fileType,
                'target_path' => $distantFolder.$newFilename,
            ]);
            $fileResource = fopen($file->getPathname(), 'r');
            if (false === $fileResource) {
                throw new FileException(sprintf('Impossible d’ouvrir le fichier : %s', $file->getPathname()));
            }
            $this->fileStorage->writeStream($distantFolder.$newFilename, $fileResource);
            fclose($fileResource);
        } catch (FileException $exception) {
            $this->logger->error(
                'Erreur lors du téléversement du fichier vers le dossier temporaire du bucket.',
                [
                    'new_filename' => $newFilename,
                    'exception' => $exception->getMessage(),
                ]
            );

            return ['error' => 'Erreur lors du téléversement.', 'message' => $exception->getMessage(), 'status' => 500];
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
            $info = pathinfo($filePath);
            $tmpDir = (string) $this->parameterBag->get('bucket_tmp_dir');

            $dirname = $info['dirname'] ?? '';
            $dirname = ('' === $dirname || '.' === $dirname) ? '' : $dirname;

            // Si le chemin commence par le tmp dir, on le retire (ex: "tmp/2026/01/" -> "2026/01/")
            if ('' !== $dirname && str_starts_with($dirname, $tmpDir)) {
                $dirname = substr($dirname, strlen($tmpDir));
            }
            $dirname = '' === $dirname ? '' : rtrim($dirname, '/').'/';

            $ext = isset($info['extension']) ? '.'.$info['extension'] : '';
            $newPath = $dirname.$info['filename'].$ext;

            if ($this->fileStorage->fileExists($newPath)) {
                $this->logger->info('Le fichier existe déjà à l’emplacement cible.', [
                    'target_filepath' => $newPath,
                ]);

                return $newPath;
            }

            if ($this->fileStorage->fileExists($filePath)) {
                $this->fileStorage->move($filePath, $newPath);
                $this->logger->info('Déplacement du fichier.', [
                    'from' => $filePath,
                    'to' => $newPath,
                ]);

                return $newPath;
            }
        } catch (FilesystemException $exception) {
            $this->logger->error('Erreur lors du déplacement du fichier.', [
                'exception' => $exception->getMessage(),
                'filepath' => $filePath,
            ]);
        }

        return null;
    }

    public function moveFromBucketTempFolder(string $filename, ?string $directory = null): ?string
    {
        $filename = null === $directory ? $filename : $directory.$filename;
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
            $this->logger->error(
                'Erreur lors de la copie du fichier avec un nouveau nom.',
                [
                    'filename' => $filename,
                    'new_filename' => $newFilename,
                    'exception' => $exception->getMessage(),
                ]
            );
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
        } catch (\Throwable $exception) {
            $this->logger->error('Erreur lors de la suppression du fichier.', [
                'exception' => $exception->getMessage(),
                'filename' => $file->getFilename() ?? '',
            ]);

            return false;
        }

        return true;
    }

    /**
     * @throws FilesystemException
     */
    public function deleteFileInBucket(File $file): void
    {
        $this->deleteFileInBucketFromFilename($file->getFilename());
    }

    /**
     * @throws FilesystemException
     */
    public function deleteFileInBucketFromFilename(string $filename): void
    {
        $this->deleteSingleFile($filename);
        $variantNames = ImageManipulationHandler::getVariantNames($filename);
        $this->deleteSingleFile($variantNames[ImageManipulationHandler::SUFFIX_RESIZE]);
        $this->deleteSingleFile($variantNames[ImageManipulationHandler::SUFFIX_THUMB]);
    }

    /**
     * @throws FilesystemException
     */
    public function deleteSingleFile(string $filename): void
    {
        if ($this->fileStorage->fileExists($filename)) {
            $this->fileStorage->delete($filename);
        }
    }

    public function uploadFromFilename(string $filename, ?string $fromFolder = null): ?string
    {
        $fromFolder = $fromFolder ?? $this->parameterBag->get('uploads_tmp_dir');
        $tmpFilepath = $fromFolder.$filename;
        $this->logger->info('Envoi du fichier vers le bucket depuis un chemin local.', [
            'filename' => $filename,
            'from_folder' => $fromFolder,
            'tmp_filepath' => $tmpFilepath,
        ]);

        try {
            $pathInfo = pathinfo($tmpFilepath);
            $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
            $newFilename = $pathInfo['filename'].$ext;

            $fileResource = fopen($tmpFilepath, 'r');
            $this->fileStorage->writeStream($newFilename, $fileResource);

            return $newFilename;
        } catch (FilesystemException $exception) {
            $this->logger->error(
                'Erreur lors de l\'envoi de fichier vers le bucket depuis un chemin local.',
                [
                    'filename' => $filename,
                    'from_folder' => $fromFolder,
                    'tmp_filepath' => $tmpFilepath,
                    'target_filename' => $newFilename,
                    'exception' => $exception->getMessage(),
                ]
            );
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
            $this->logger->info('Téléversement d\'un fichier vers le bucket.', [
                'original_filename' => $file->getClientOriginalName(),
                'target_filename' => $newFilename,
            ]);

            $fileResource = fopen($tmpFilepath, 'r');
            if (false === $fileResource) {
                throw new FileException(sprintf('Impossible d’ouvrir le fichier : %s', $file->getPathname()));
            }
            $this->fileStorage->writeStream($newFilename, $fileResource);
            fclose($fileResource);

            return $newFilename;
        } catch (FilesystemException $exception) {
            $this->logger->error(
                'Erreur lors du téléversement du fichier vers le bucket.',
                [
                    'target_filename' => $newFilename,
                    'exception' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }

    public function getTmpFilepath(string $filename): ?string
    {
        $tmpFilepath = $bucketFilepath = $fileResize = null;
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
            $this->logger->error(
                'Erreur lors de la création d\’une copie locale temporaire depuis le bucket.',
                [
                    'filename' => $filename,
                    'resize_variant' => $fileResize,
                    'tmp_filepath' => $tmpFilepath,
                    'bucket_filepath' => $bucketFilepath,
                    'exception' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }

    /**
     * @return resource|null
     */
    public function openReadStream(string $filename)
    {
        try {
            $variantNames = ImageManipulationHandler::getVariantNames($filename);
            $resizedFilename = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE] ?? null;

            if ($resizedFilename && $this->fileStorage->fileExists($resizedFilename)) {
                $filename = $resizedFilename;
            }

            $stream = $this->fileStorage->readStream($filename);

            return \is_resource($stream) ? $stream : null;
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Impossible d’ouvrir un flux de lecture depuis le bucket.',
                [
                    'filename' => $filename,
                    'exception' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    /**
     * @throws FilesystemException
     */
    public function createTmpFileFromBucket(string $from, string $to): void
    {
        try {
            $this->logger->info('Création d\'un fichier temporaire local depuis le bucket.', [
                'from' => $from,
                'to' => $to,
            ]);
            $resourceBucket = $this->fileStorage->read($from);
            $resourceFileSystem = @fopen($to, 'w');
            if (false === $resourceFileSystem) {
                throw new FileException(sprintf('Impossible de créer le fichier temporaire : %s', $to));
            }

            $bytesWritten = fwrite($resourceFileSystem, $resourceBucket);
            if (false === $bytesWritten) {
                throw new FileException(sprintf('Erreur d’écriture dans le fichier temporaire : %s', $to));
            }

            fclose($resourceFileSystem);
        } catch (FilesystemException $exception) {
            $errorMessage = 'Erreur lors de la création du fichier temporaire depuis le bucket.';
            $this->logger->error(
                $errorMessage,
                [
                    'from' => $from,
                    'to' => $to,
                    'exception' => $exception->getMessage(),
                ]
            );
            throw new FileException($errorMessage, 0, $exception);
        }
    }

    public function getFileSize(string $filename): ?int
    {
        try {
            $variantNames = ImageManipulationHandler::getVariantNames($filename);
            $fileResize = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE];
            if ($this->fileStorage->fileExists($fileResize)) {
                return $this->fileStorage->fileSize($fileResize);
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Erreur lors de la récupération de la taille du fichier.', [
                'filename' => $filename,
                'filename_resized' => $fileResize ?? null,
                'exception' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    public function hasVariants(string $filename): bool
    {
        try {
            $variantNames = ImageManipulationHandler::getVariantNames($filename);
            if ($this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])
                && $this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])
            ) {
                return true;
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Erreur lors de la vérification des variants de fichier.', [
                'filename' => $filename,
                'exception' => $exception->getMessage(),
                'variant_names' => $variantNames ?? null,
            ]);
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
