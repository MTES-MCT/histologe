<?php

namespace App\Exception\File;

use App\Service\UploadHandlerService;

class UnsupportedFileFormatException extends \Exception
{
    public function __construct(?string $mimeType = null, ?string $fileType = 'document')
    {
        parent::__construct(
            sprintf(
                'Le format %s n\'est pas supporté, merci d\'essayer avec un format %s',
                $mimeType,
                UploadHandlerService::getAcceptedExtensions($fileType)
            )
        );
    }
}
