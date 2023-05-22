<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Service\Esabora\Response\DossierResponseInterface;

interface EsaboraServiceInterface
{
    public function getStateDossier(Affectation $affectation): DossierResponseInterface;
}
