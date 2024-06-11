<?php

namespace App\Exception\File;

class EmptyFileException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Le fichier est vide où il y a eu un problème de téléchargement. Veuillez réessayer un fichier non vide.');
    }
}
