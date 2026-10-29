<?php

namespace App\Service\Gouv\Insee\Response;

class SiretInformation
{
    public function __construct(
        public string $siret,
        public string $codeCommune,
    ) {
    }
}
