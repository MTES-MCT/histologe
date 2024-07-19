<?php

namespace App\Service\Security;

use App\Service\UploadHandlerService;
use Sineflow\ClamAV\Exception\FileScanException;
use Sineflow\ClamAV\Exception\SocketException;
use Sineflow\ClamAV\Scanner;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileScanner
{
    public function __construct(
        private readonly Scanner $scanner,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @throws FileScanException
     * @throws SocketException
     */
    public function isClean(string $filePath, ?bool $copy = true): bool
    {
        if ($copy) {
            $copiedFilepath = $this->parameterBag->get('uploads_tmp_dir').'clamav_'.uniqid();
            file_put_contents($copiedFilepath, file_get_contents($filePath));
        } else {
            $copiedFilepath = $filePath;
        }

        $scannedFile = $this->scanner->scan($copiedFilepath);

        return $scannedFile->isClean();
    }
}
