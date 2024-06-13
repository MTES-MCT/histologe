<?php

namespace App\Exception\File;

class EmptyFileException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Le fichier est vide ou il y a eu un problème de téléchargement. Merci de réessayer ou d\'envoyer un autre fichier.');
    }
}
