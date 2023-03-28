<?php

namespace App\Exception\File;

class UnsupportedFileFormatException extends \Exception
{
    public function __construct(?string $mimeType = null)
    {
        parent::__construct(
            sprintf('Le format %s n\'est pas supporté, merci d\'essayer avec un format JPEG ou PNG', $mimeType)
        );
    }
}
