<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\Files\HeicToJpegConverter;
use App\Service\ImageManipulationHandler;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementFileProcessor
{
    private array $errors = [];
    private File $lastFile;

    public function __construct(
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly LoggerInterface $logger,
        private readonly FilenameGenerator $filenameGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FileFactory $fileFactory,
        private readonly ImageManipulationHandler $imageManipulationHandler,
    ) {
    }

    public function process(
        array $files,
        string $inputName,
        ?DocumentType $documentType = DocumentType::AUTRE
    ): array {
        $fileList = $descriptionList = [];
        $withTokenGenerated = false;
        foreach ($files[$inputName] as $key => $file) {
            if ($file instanceof UploadedFile
                && \in_array($file->getMimeType(), HeicToJpegConverter::HEIC_FORMAT)
            ) {
                $message = <<<ERROR
                    Les fichiers de format HEIC/HEIF ne sont pas pris en charge,
                    merci de convertir votre image en JPEG ou en PNG avant de l'envoyer.
                    ERROR;
                $this->logger->error($message);
                $this->errors[] = $message;
            } else {
                $inputTypeDetection = $inputName;
                try {
                    if ($file instanceof UploadedFile) {
                        $filename = $this->uploadHandlerService->uploadFromFile(
                            $file,
                            $this->filenameGenerator->generate($file)
                        );
                        $title = $this->filenameGenerator->getTitle();

                        if (\in_array($file->getMimeType(), ImageManipulationHandler::IMAGE_MIME_TYPES)) {
                            $this->imageManipulationHandler->setUseTmpDir(false)->resize($filename)->thumbnail($filename);
                        }else{
                            $inputTypeDetection = 'documents';
                        }
                    } else {
                        $filename = $this->uploadHandlerService->moveFromBucketTempFolder($file);
                        $title = $key;
                        $withTokenGenerated = true;
                    }
                } catch (\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    $this->errors[] = $exception->getMessage();
                    continue;
                }
                if (!empty($filename)) {
                    $descriptionList[] = $this->generateListItemDescription($filename, $title, $withTokenGenerated);
                    $fileList[] = $this->createFileItem($filename, $title, $inputTypeDetection, $documentType);
                }
            }
        }

        return [$fileList, $descriptionList];
    }

    public function addFilesToSignalement(
        array $fileList,
        Signalement $signalement,
        ?UserInterface $user = null,
        ?Intervention $intervention = null,
    ): void {
        foreach ($fileList as $fileItem) {
            $file = $this->fileFactory->createInstanceFrom(
                filename: $fileItem['file'],
                title: $fileItem['title'],
                type: $fileItem['type'],
                user: $user,
                intervention: $intervention,
                documentType: $fileItem['documentType'],
            );
            $file->setSize($this->uploadHandlerService->getFileSize($file->getFilename()));
            $file->setIsVariantsGenerated($this->uploadHandlerService->hasVariants($file->getFilename()));
            $signalement->addFile($file);
            $this->lastFile = $file;
        }
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function generateListItemDescription(
        string $filename,
        string $title,
        bool $withTokenGenerated = false,
    ): string {
        $queryTokenUrl = $withTokenGenerated ? '&t=___TOKEN___' : '';

        $fileUrl = $this->urlGenerator->generate(
            'show_uploaded_file',
            ['folder' => '_up', 'filename' => $filename]
        )
            .$queryTokenUrl;

        return '<li><a class="fr-link" target="_blank" href="'
            .$fileUrl
            .'">'
            .$title
            .'</a></li>';
    }

    private function createFileItem(
        string $filename,
        string $title,
        string $inputName,
        DocumentType $documentType
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
