<?php

namespace App\Exception;

class MaxUploadSizeExceededException extends \Exception
{
    public function __construct(int $filesize)
    {
        parent::__construct(sprintf('Le fichier dépasse %s MB', $filesize / 1024 / 1024));
    }
}
