<?php

namespace App\Service\Security;

use Psr\Log\LoggerInterface;
use Sineflow\ClamAV\Exception\FileScanException;
use Sineflow\ClamAV\Exception\SocketException;
use Sineflow\ClamAV\Scanner;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Uid\Uuid;

class FileScanner
{
    public function __construct(
        private readonly Scanner $scanner,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'CLAMAV_SCAN_ENABLE')]
        private bool $clamavScanEnable,
    ) {
    }

    /**
     * @throws FileScanException
     * @throws SocketException
     */
    public function isClean(string $filePath, ?bool $copy = true): bool
    {
        if (!$this->clamavScanEnable) {
            return true;
        }

        if (empty($filePath)) {
            return false;
        }
        if ($copy) {
            $copiedFilepath = $this->parameterBag->get('uploads_tmp_dir').'clamav_'.Uuid::v4();
            file_put_contents($copiedFilepath, file_get_contents($filePath));
        } else {
            $copiedFilepath = $filePath;
        }

        $scannedFile = $this->scanner->scan($copiedFilepath);
        if (!$scannedFile->isClean()) {
            $this->logger->error('Le fichier semble infecté', [
                'filename' => $scannedFile->getFileName(),
                'response' => $scannedFile->getRawResponse(),
                'virus' => $scannedFile->getVirusName(),
            ]);

            return false;
        }

        return true;
    }
}
