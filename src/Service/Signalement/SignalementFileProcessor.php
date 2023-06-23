<?php

namespace App\Service\Signalement;

use App\Entity\File;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Service\Files\FilenameGenerator;
use App\Service\Files\HeicToJpegConverter;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementFileProcessor
{
    private array $errors = [];

    public function __construct(
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly LoggerInterface $logger,
        private readonly FilenameGenerator $filenameGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function process(array $files, string $inputName): array
    {
        $fileList = $descriptionList = [];

        foreach ($files[$inputName] as $file) {
            if ($file instanceof UploadedFile && \in_array($file->getMimeType(), HeicToJpegConverter::HEIC_FORMAT)) {
                $message = <<<ERROR
                    Les fichiers de format HEIC/HEIF ne sont pas pris en charge,
                    merci de convertir votre image en JPEG ou en PNG avant de l'envoyer.
                    ERROR;
                $this->logger->error($message);
                $this->errors[] = $message;
            } else {
                try {
                    if ($file instanceof UploadedFile) {
                        $filename = $this->uploadHandlerService->uploadFromFile($file, $this->filenameGenerator->generate($file));
                    } else {
                        $filename = $this->uploadHandlerService->uploadFromFilename($file);
                    }
                } catch (MaxUploadSizeExceededException|\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    $this->errors[] = $exception->getMessage();
                    continue;
                }
                if (!empty($filename)) {
                    $title = $this->filenameGenerator->getTitle();
                    $descriptionList[] = $this->generateListItemDescription($filename, $title);
                    $fileList[] = $this->createFileItem($filename, $title, $inputName);
                }
            }
        }

        return [$fileList, $descriptionList];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function generateListItemDescription(
        string $filename,
        string $title,
    ): string {
        $fileUrl = $this->urlGenerator->generate(
            'show_uploaded_file',
            ['folder' => '_up', 'filename' => $filename]
        );

        return '<li><a class="fr-link" target="_blank" href="'
            .$fileUrl
            .'">'
            .$title
            .'</a></li>';
    }

    private function createFileItem(string $filename, string $title, string $inputName): array
    {
        return [
            'file' => $filename,
            'title' => $title,
            'date' => new \DateTimeImmutable(),
            'type' => 'documents' === $inputName ? File::FILE_TYPE_DOCUMENT : File::FILE_TYPE_PHOTO,
        ];
    }
}
