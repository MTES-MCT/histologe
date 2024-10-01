<?php

namespace App\Controller;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Service\ImageManipulationHandler;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class FileController extends AbstractController
{
    #[Route('/show/{uuid}', name: 'show_file')]
    public function showFile(
        File $file,
        Request $request,
        LoggerInterface $logger,
        FilesystemOperator $fileStorage,
    ): BinaryFileResponse {
        try {
            $variant = $request->query->get('variant');
            $filename = $file->getFilename();
            $documentType = $file->getDocumentType();
            $variantNames = ImageManipulationHandler::getVariantNames($filename);

            if ('thumb' == $variant && $fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
                $filename = $variantNames[ImageManipulationHandler::SUFFIX_THUMB];
            } elseif ($fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])) {
                $filename = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE];
            }
            if (!$fileStorage->fileExists($filename)) {
                throw new \Exception('File "'.$filename.'" not found');
            }
            $tmpFilepath = $this->getParameter('uploads_tmp_dir').$filename;
            $bucketFilepath = $this->getParameter('url_bucket').'/'.$filename;
            $content = file_get_contents($bucketFilepath);
            file_put_contents($tmpFilepath, $content);
            $file = new SymfonyFile($tmpFilepath);

            if (DocumentType::EXPORT === $documentType) {
                return (new BinaryFileResponse($file))->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    $file->getFilename()
                );
            }

            return new BinaryFileResponse($file);
        } catch (\Throwable $exception) {
            $logger->error($exception->getMessage());
        }

        return new BinaryFileResponse(
            new SymfonyFile($this->getParameter('images_dir').'image-404.png'),
        );
    }
}
