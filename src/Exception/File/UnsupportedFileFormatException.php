<?php

namespace App\Exception\File;

use App\Service\UploadHandlerService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UnsupportedFileFormatException extends \Exception
{
    public function __construct(UploadedFile $file, ?string $fileType = null)
    {
        parent::__construct(self::getFileFormatErrorMessage($file, $fileType));
    }

    public static function getFileFormatErrorMessage(UploadedFile $file, ?string $fileType = null): string
    {
        $ext = $file->getClientOriginalExtension();
        $mime = $file->getMimeType();

        if (!str_ends_with($mime, '/'.$ext)) {
            return \sprintf(
                'Le fichier a une extension %s mais est au format %s. Les fichiers de format %s ne sont pas pris en charge, merci de choisir un fichier au format %s',
                $ext,
                $mime,
                $mime,
                UploadHandlerService::getAcceptedExtensions($fileType)
            );
        }

        return \sprintf(
            'Les fichiers de format %s ne sont pas pris en charge, merci de choisir un fichier au format %s',
            $mime,
            UploadHandlerService::getAcceptedExtensions($fileType)
        );
    }
}
