<?php

namespace App\Service\Signalement;

use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;

class SignalementFileProcessor
{
    public function __construct(
        private UploadHandlerService $uploadHandlerService,
        private LoggerInterface $logger,
        private FileFactory $fileFactory,
        private FilenameGenerator $filenameGenerator
    ) {
    }

    public function process(): array
    {
        return [];
    }

    private function generateFileUrl(string $filename): string
    {

    }

    private function createFileItem(string $filename, string $title, string $inputName): array
    {

    }

    private function generateListItemDescription(string $filename, string $title): string
    {

    }
}