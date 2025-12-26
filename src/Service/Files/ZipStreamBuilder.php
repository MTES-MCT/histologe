<?php

namespace App\Service\Files;

use App\Entity\File;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use ZipStream\Exception\OverflowException;
use ZipStream\ZipStream;

class ZipStreamBuilder
{
    private ?ZipStream $zipStream = null;
    private mixed $outputStream = null;
    private ?string $zipPath = null;
    private int $countFile = 0;

    public function __construct(
        private readonly UploadHandlerService $uploadHandler,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
    ) {
        $this->zipStream = null;
    }

    /**
     * @throws \Exception
     */
    public function create(string $zipBaseName): self
    {
        $this->logger->info('Creating ZIP archive', [
            'zipBaseName' => $zipBaseName,
        ]);
        if (null !== $this->zipStream) {
            throw new \LogicException('ZIP archive is already initialized');
        }

        $tmpDir = $this->parameterBag->get('uploads_tmp_dir');
        $this->zipPath = $tmpDir.$zipBaseName;

        if (false === $this->zipPath) {
            throw new \Exception(sprintf('Temporary directory is not valid or not writable: %s', $tmpDir));
        }

        $this->outputStream = fopen($this->zipPath, 'w');
        if (false === $this->outputStream) {
            @unlink($this->zipPath);
            throw new \Exception('Unable to create temporary ZIP file.');
        }

        $this->zipStream = new ZipStream(
            outputStream: $this->outputStream,
            sendHttpHeaders: false,
            outputName: $zipBaseName,
        );

        return $this;
    }

    public function add(File $file): self
    {
        if (null === $this->zipStream || null === $this->outputStream || null === $this->zipPath) {
            throw new \LogicException('ZIP archive is not initialized. Call create() before add() or addMany()');
        }

        $stream = $this->uploadHandler->openReadStream($filename = $file->getFilename());
        if (!\is_resource($stream)) {
            $this->logger->warning('File not found in storage', ['filename' => $filename]);

            return $this;
        }

        try {
            $this->zipStream->addFileFromStream($filename, $stream);
            ++$this->countFile;
            $this->logger->info('Added file to ZIP archive', ['filename' => $filename]);
        } catch (\Throwable $exception) {
            $this->clear();
            $this->logger->error($exception->getMessage());
        } finally {
            fclose($stream);
        }

        return $this;
    }

    /**
     * @param iterable<File> $files
     */
    public function addMany(iterable $files): self
    {
        foreach ($files as $file) {
            $this->add($file);
        }

        return $this;
    }

    /**
     * @throws OverflowException
     * @throws \Exception
     */
    public function close(): string
    {
        if (null === $this->zipStream || null === $this->outputStream || null === $this->zipPath) {
            throw new \LogicException('ZIP archive is not initialized. Call create() before add() or addMany()');
        }

        if (0 === $this->countFile) {
            $this->clear();
            throw new \Exception('ZIP archive is empty: no files were added.');
        }

        $this->zipStream->finish();

        if (\is_resource($this->outputStream)) {
            fclose($this->outputStream);
        }

        $zipPath = $this->zipPath;
        $this->clear();
        $this->logger->info('ZIP archive created', ['zipPath' => $zipPath]);

        return $zipPath;
    }

    private function clear(): void
    {
        $this->zipStream = null;
        $this->outputStream = null;
        $this->zipPath = null;
        $this->countFile = 0;
    }
}
