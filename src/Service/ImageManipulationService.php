<?php

namespace App\Service;

use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageManipulationService
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

    private $imageManager;
    private $tmp = true;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
        ) {
        $this->imageManager = new ImageManager();
    }

    public function setTmp(bool $tmp)
    {
        $this->tmp = $tmp;
    }

    public function resize($path, $max = self::DEFAULT_SIZE_RESIZE)
    {
        $image = $this->imageManager->make($this->fileStorage->readStream($path));
        $image->resize($max, $max, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $resource = $image->stream()->detach();
        $this->fileStorage->writeStream($this->getNewPath($path, self::SUFFIX_RESIZE), $resource);
    }

    public function thumbnail($path, $size = self::DEFAULT_SIZE_THUMB)
    {
        $image = $this->imageManager->make($this->fileStorage->readStream($path));
        $image->fit($size, $size);
        $resource = $image->stream()->detach();
        $this->fileStorage->writeStream($this->getNewPath($path, self::SUFFIX_THUMB), $resource);
    }

    private function getNewPath($path, $suffix)
    {
        $pathInfo = pathinfo($path);
        $newName = $pathInfo['filename'].$suffix.'.'.$pathInfo['extension'];
        $newPath = $newName;
        if ($this->tmp) {
            $newPath = $this->parameterBag->get('bucket_tmp_dir').$newName;
        }

        return $newPath;
    }
}
