<?php

namespace App\Service;

use App\Entity\File;
use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class ImageManipulationHandler
{
    public const string SUFFIX_RESIZE = '_resize';
    public const string SUFFIX_THUMB = '_thumb';

    private const int DEFAULT_SIZE_RESIZE = 1000;
    private const int DEFAULT_SIZE_THUMB = 400;
    private const int DEFAULT_SIZE_AVATAR = 150;
    private bool $useTmpDir = true;
    private ?string $imagePath = null;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
        private readonly ImageManager $imageManager,
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

    /**
     * @throws \Throwable
     */
    public function avatar(?string $path = null, ?int $size = self::DEFAULT_SIZE_AVATAR): self
    {
        if ($path) {
            $this->imagePath = $path;
        }
        $image = $this->imageManager->make($this->fileStorage->readStream($this->imagePath));
        $image->fit($size, $size);
        $resource = $image->stream()->detach();
        $this->fileStorage->writeStream($path, $resource);

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function rotate(File $file, int $angle): void
    {
        $variantNames = self::getVariantNames($file->getFilename());

        foreach ($variantNames as $variant) {
            if (!$this->fileStorage->fileExists($variant)) {
                continue;
            }
            $image = $this->imageManager->make($this->fileStorage->readStream($variant));
            $image->rotate($angle);
            $resource = $image->stream()->detach();
            $this->fileStorage->writeStream($variant, $resource);
        }
    }

    /**
     * @throws FilesystemException
     */
    public function getFilePath(File $file): string
    {
        $variantNames = self::getVariantNames($file->getFilename());
        $filename = $variantNames[self::SUFFIX_RESIZE];
        $originalFilename = $file->getFilename();
        if ($this->fileStorage->fileExists($filename)) {
            return $this->parameterBag->get('url_bucket').'/'.$filename;
        }
        if ($this->fileStorage->fileExists($originalFilename)) {
            return $this->parameterBag->get('url_bucket').'/'.$originalFilename;
        }

        throw new FileNotFoundException($filename);
    }

    private function getNewPath(string $suffix): string
    {
        $pathInfo = pathinfo($this->imagePath);
        $dirname = $pathInfo['dirname'] ?? '';
        $dirname = ('' === $dirname || '.' === $dirname) ? '' : $dirname.'/';
        $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
        $newName = $dirname.$pathInfo['filename'].$suffix.$ext;
        $newPath = $newName;
        if ($this->useTmpDir && !str_starts_with($newName, $this->parameterBag->get('bucket_tmp_dir'))) {
            $newPath = $this->parameterBag->get('bucket_tmp_dir').$newName;
        }

        return $newPath;
    }

    /**
     * @return array<string, string>
     */
    public static function getVariantNames(string $filename): array
    {
        $pathInfo = pathinfo($filename);
        $ext = \array_key_exists('extension', $pathInfo) ? '.'.$pathInfo['extension'] : '';
        $dirname = $pathInfo['dirname'] ?? '';
        $dirname = ('' === $dirname || '.' === $dirname) ? '' : $dirname.'/';

        $resize = $dirname.$pathInfo['filename'].self::SUFFIX_RESIZE.$ext;
        $thumb = $dirname.$pathInfo['filename'].self::SUFFIX_THUMB.$ext;

        return [self::SUFFIX_RESIZE => $resize, self::SUFFIX_THUMB => $thumb];
    }
}
