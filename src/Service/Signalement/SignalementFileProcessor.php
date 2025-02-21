<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Exception\File\EmptyFileException;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\ImageManipulationHandler;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SignalementFileProcessor
{
    private array $errors = [];
    private File $lastFile;

    public function __construct(
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly LoggerInterface $logger,
        private readonly FilenameGenerator $filenameGenerator,
        private readonly FileFactory $fileFactory,
        private readonly ImageManipulationHandler $imageManipulationHandler,
        private readonly FileScanner $fileScanner,
        #[Autowire(env: 'CLAMAV_SCAN_ENABLE')]
        private bool $clamavScanEnable,
    ) {
    }

    public function process(
        array $files,
        string $inputName,
        ?DocumentType $documentType = DocumentType::AUTRE,
    ): array {
        $fileList = [];
        foreach ($files[$inputName] as $key => $file) {
            $fileSizeOk = false;
            if ($file instanceof UploadedFile) {
                try {
                    if (!$this->fileScanner->isClean($file->getPathname())) {
                        $message = 'Le fichier '.$file->getClientOriginalName().' est infecté par un virus.';
                        $this->errors[] = $message;
                        $this->logger->error($message);
                        continue;
                    }
                } catch (\Exception $exception) {
                    $this->errors[] = $exception->getMessage();
                    $this->logger->error($exception->getMessage());
                    continue;
                }
                try {
                    $fileSizeOk = $this->uploadHandlerService->isFileSizeOk($file);
                } catch (MaxUploadSizeExceededException|EmptyFileException $exception) {
                    $this->errors[] = $exception->getMessage();
                    $this->logger->error($exception->getMessage());
                }
            } else {
                $fileSizeOk = true;
            }

            if ($fileSizeOk) {
                if (
                    $file instanceof UploadedFile
                    && File::INPUT_NAME_DOCUMENTS === $inputName
                    && !UploadHandlerService::isAcceptedDocumentFormat($file, $inputName)
                ) {
                    $message = UnsupportedFileFormatException::getFileFormatErrorMessage($file, 'document');
                    $fileInfo = ' ( Fichier : '.$file->__toString().' MimeType : '.$file->getMimeType().' )';
                    $this->logger->error($message.$fileInfo);
                    $this->errors[] = $message;
                } elseif (
                    $file instanceof UploadedFile
                    && File::INPUT_NAME_PHOTOS === $inputName
                    && !ImageManipulationHandler::isAcceptedPhotoFormat($file, $inputName)
                ) {
                    $message = UnsupportedFileFormatException::getFileFormatErrorMessage($file, 'photo');
                    $fileInfo = ' ( Fichier : '.$file->__toString().' MimeType : '.$file->getMimeType().' )';
                    $this->logger->error($message.$fileInfo);
                    $this->errors[] = $message;
                } else {
                    $inputTypeDetection = $inputName;
                    try {
                        if ($file instanceof UploadedFile) {
                            $filename = $this->uploadHandlerService->uploadFromFile(
                                $file,
                                $this->filenameGenerator->generate($file),
                                $inputTypeDetection
                            );
                            $title = $this->filenameGenerator->getTitle();

                            if (\in_array($file->getMimeType(), File::IMAGE_MIME_TYPES)) {
                                $this->imageManipulationHandler->setUseTmpDir(false)->resize($filename)->thumbnail($filename);
                            } else {
                                $inputTypeDetection = 'documents';
                            }
                        } else {
                            $filename = $this->uploadHandlerService->moveFromBucketTempFolder($file);
                            $title = $key;
                        }
                    } catch (\Exception $exception) {
                        $this->logger->error($exception->getMessage());
                        $this->errors[] = $exception->getMessage();
                        continue;
                    }
                    if (!empty($filename)) {
                        $fileList[] = $this->createFileItem($filename, $title, $inputTypeDetection, $documentType);
                    }
                }
            }
        }

        return $fileList;
    }

    public function addFilesToSignalement(
        array $fileList,
        Signalement $signalement,
        ?User $user = null,
        ?Intervention $intervention = null,
        ?bool $isWaitingSuivi = false,
        ?bool $isTemp = false,
    ): array {
        $list = [];
        foreach ($fileList as $fileItem) {
            $file = $this->fileFactory->createInstanceFrom(
                filename: $fileItem['file'],
                title: $fileItem['title'],
                type: $fileItem['type'],
                user: $user,
                intervention: $intervention,
                documentType: $fileItem['documentType'],
                isWaitingSuivi: $isWaitingSuivi,
                isTemp: $isTemp,
                scannedAt: $this->clamavScanEnable ? new \DateTimeImmutable() : null
            );
            $fileSize = $this->uploadHandlerService->getFileSize($file->getFilename());
            $file->setSize(null !== $fileSize ? (string) $fileSize : null);
            $file->setIsVariantsGenerated($this->uploadHandlerService->hasVariants($file->getFilename()));
            $signalement->addFile($file);
            $this->lastFile = $file;
            $list[] = $file;
        }

        return $list;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorMessages(): string
    {
        return implode('<br>', $this->errors);
    }

    private function createFileItem(
        string $filename,
        string $title,
        string $inputName,
        DocumentType $documentType,
    ): array {
        return [
            'file' => $filename,
            'title' => $title,
            'date' => new \DateTimeImmutable(),
            'type' => 'documents' === $inputName ? File::FILE_TYPE_DOCUMENT : File::FILE_TYPE_PHOTO,
            'documentType' => $documentType,
        ];
    }

    public function getLastFile(): File
    {
        return $this->lastFile;
    }
}
