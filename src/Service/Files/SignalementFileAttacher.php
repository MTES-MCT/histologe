<?php

namespace App\Service\Files;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SignalementFileAttacher
{
    public function __construct(
        private readonly FileFactory $fileFactory,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly FileScanner $fileScanner,
        #[Autowire(env: 'CLAMAV_SCAN_ENABLE')]
        private readonly bool $clamavScanEnable,
    ) {
    }

    /**
     * @param array<string, mixed> $fileData
     */
    public function createAndAttach(
        Signalement $signalement,
        array $fileData,
        ?User $uploadedBy = null,
    ): void {
        $file = $this->fileFactory->createFromFileArray(file: $fileData, signalement: $signalement);
        $this->uploadHandlerService->moveFromBucketTempFolder($file->getFilename());
        $fileSize = $this->uploadHandlerService->getFileSize($file->getFilename());

        $file->setSize(null !== $fileSize ? (string) $fileSize : null);
        $file->setIsVariantsGenerated($this->uploadHandlerService->hasVariants($file->getFilename()));

        if (null !== $uploadedBy) {
            $file->setUploadedBy($uploadedBy);
        }

        $this->scanIfNeeded($file);

        $signalement->addFile($file);
    }

    private function scanIfNeeded(File $file): void
    {
        if (!$this->clamavScanEnable) {
            return;
        }

        $file->setScannedAt(new \DateTimeImmutable());
        if (!str_ends_with(strtolower($file->getFilename()), '.pdf')) {
            return;
        }

        $filepath = $this->uploadHandlerService->getTmpFilepath($file->getFilename());

        if ($filepath && !$this->fileScanner->isClean($filepath)) {
            $file->setIsSuspicious(true);
        }
    }
}
