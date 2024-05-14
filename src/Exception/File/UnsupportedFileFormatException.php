<?php

namespace App\Exception\File;

class UnsupportedFileFormatException extends \Exception
{
    public function __construct(?string $mimeType = null, ?array $acceptedFormat = ['JPG', 'PNG'])
    {
        parent::__construct(
            sprintf(
                'Le format %s n\'est pas supporté, merci d\'essayer avec un format %s',
                $mimeType,
                implode(',', $acceptedFormat)
            )
        );
    }
}
