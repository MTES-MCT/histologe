<?php

namespace App\Service;

use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageManipulationHandler
{
    public const SUFFIX_RESIZE = '_resize';
    public const SUFFIX_THUMB = '_thumb';
    public const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    private const DEFAULT_SIZE_RESIZE = 1000;
    private const DEFAULT_SIZE_THUMB = 400;
    private bool $useTmpDir = true;
    private string $imagePath;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
        private readonly ImageManager $imageManager
        ) {
    }

    public function setUseTmpDir(bool $tmp): self
    {
        $this->useTmpDir = $tmp;

        return $this;
    }

    public function setImagePath(string $path): self
    {
        $this->imagePath = $path;

        return $this;
    }

    public function resize(?string $path = null, ?int $max = self::DEFAULT_SIZE_RESIZE): self
    {
        if ($path) {
            $this->imagePath = $path;
        }
        $image = $this->imageManager->make($this->fileStorage->readStream($this->imagePath));
        $image->resize($max, $max, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $resource = $image->stream()->detach();
        $this->fileStorage->writeStream($this->getNewPath(self::SUFFIX_RESIZE), $resource);

        return $this;
    }

    public function thumbnail(?string $path = null, ?int $size = self::DEFAULT_SIZE_THUMB): self
    {
        if ($path) {
            $this->imagePath = $path;
        }
        $image = $this->imageManager->make($this->fileStorage->readStream($this->imagePath));
        $image->fit($size, $size);
        $resource = $image->stream()->detach();
        $this->fileStorage->writeStream($this->getNewPath(self::SUFFIX_THUMB), $resource);

        return $this;
    }

    private function getNewPath(string $suffix): string
    {
        $pathInfo = pathinfo($this->imagePath);
        $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
        $newName = $pathInfo['filename'].$suffix.$ext;
        $newPath = $newName;
        if ($this->useTmpDir) {
            $newPath = $this->parameterBag->get('bucket_tmp_dir').$newName;
        }

        return $newPath;
    }
}
