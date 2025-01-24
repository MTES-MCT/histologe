<?php

namespace App\Service\Files;

use App\Service\ImageManipulationHandler;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

readonly class ImageVariantProvider
{
    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws \Exception
     */
    public function getFileVariant(string $filename, ?string $variant = null): File
    {
        $variantNames = ImageManipulationHandler::getVariantNames($filename);
        if ('thumb' === $variant && $this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
            $filename = $variantNames[ImageManipulationHandler::SUFFIX_THUMB];
        } elseif ($this->fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])) {
            $filename = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE];
        }
        if (!$this->fileStorage->fileExists($filename)) {
            throw new \Exception('File "'.$filename.'" not found');
        }
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
        $content = file_get_contents($bucketFilepath);
        file_put_contents($tmpFilepath, $content);

        return new File($tmpFilepath);
    }
}
