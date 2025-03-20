<?php

namespace App\Service\Security;

use Sineflow\ClamAV\Exception\FileScanException;
use Sineflow\ClamAV\Exception\SocketException;
use Sineflow\ClamAV\Scanner;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Uid\Uuid;

class FileScanner
{
    public function __construct(
        private readonly Scanner $scanner,
        private readonly ParameterBagInterface $parameterBag,
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

        $yaraScanResult = $this->scanWithYara($copiedFilepath);
        if ($yaraScanResult) {
            return false;
        }

        $scannedFile = $this->scanner->scan($copiedFilepath);

        return $scannedFile->isClean();
    }

    private function scanWithYara(string $filePath): bool
    {
        $yaraPath = '/usr/bin/yara';
        $rulePath = '/etc/yara/rules/PDF_Containing_JavaScript.yar';
        $realFilePath = realpath($filePath);
        if (false === $realFilePath) {
            throw new \Exception("Le fichier n'existe pas ou n'est pas accessible : $filePath");
        }
        if (!file_exists($filePath)) {
            throw new \Exception("Le fichier n'existe pas : $filePath");
        }
        if (!is_readable($filePath)) {
            throw new \Exception("Le fichier n'est pas lisible : $filePath");
        }

        $process = new Process([$yaraPath, $rulePath, $filePath]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        return '' !== $output;
    }
}
