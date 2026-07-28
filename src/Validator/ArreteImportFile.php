<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class ArreteImportFile extends Constraint
{
    public string $missingHeadersMessage = 'Le fichier CSV ne contient pas les colonnes attendues. Colonnes manquantes : {{ headers }}.';
    public string $emptyFileMessage = 'Le fichier CSV est vide ou ne contient pas de données.';
    public string $tooManyLinesMessage = 'Le fichier CSV ne peut pas contenir plus de 50 lignes de données.';
    public string $duplicateLinesMessage = 'Le fichier CSV contient des lignes en doublon aux lignes : {{ lines }}.';
}
