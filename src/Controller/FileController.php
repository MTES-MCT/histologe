<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\Files\ImageVariantProvider;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class FileController extends AbstractController
{
    #[Route('/show/{uuid:file}', name: 'show_file')]
    public function showFile(
        File $file,
        Request $request,
        LoggerInterface $logger,
        ImageVariantProvider $imageVariantProvider,
    ): BinaryFileResponse {
        try {
            $variant = $request->query->get('variant');
            $filename = $file->getFilename();
            $file = $imageVariantProvider->getFileVariant($filename, $variant);

            return (new BinaryFileResponse($file))->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $file->getFilename()
            );
        } catch (\Throwable $exception) {
            $logger->error($exception->getMessage());
        }

        return new BinaryFileResponse(
            new SymfonyFile($this->getParameter('images_dir').'image-404.png'),
        );
    }
}
