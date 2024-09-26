<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\Response\DossierResponseInterface;

interface EsaboraServiceInterface
{
    public function getStateDossier(Affectation $affectation): DossierResponseInterface;
}
