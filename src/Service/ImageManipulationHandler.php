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
    public const IMAGE_EXTENSION = [
        'jpeg',
        'jpg',
        'png',
        'gif',
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

    /**
     * @throws \Throwable
     */
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

    /**
     * @throws \Throwable
     */
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

    public static function getVariantNames(string $filename): array
    {
        $pathInfo = pathinfo($filename);
        $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
        $resize = $pathInfo['filename'].self::SUFFIX_RESIZE.$ext;
        $thumb = $pathInfo['filename'].self::SUFFIX_THUMB.$ext;

        return [self::SUFFIX_RESIZE => $resize, self::SUFFIX_THUMB => $thumb];
    }
}
