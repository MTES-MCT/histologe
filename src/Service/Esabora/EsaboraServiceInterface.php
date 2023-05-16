<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Service\Esabora\Response\DossierCollectionResponseInterface;
use App\Service\Esabora\Response\DossierResponseInterface;

interface EsaboraServiceInterface
{
    public function getStateDossier(Affectation $affectation): ?DossierResponseInterface;

    public function getVisiteDossier(Affectation $affectation): ?DossierCollectionResponseInterface;

    public function getArreteDossier(Affectation $affectation): ?DossierCollectionResponseInterface;
}
