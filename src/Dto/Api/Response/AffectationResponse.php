<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\Affectation;

class AffectationResponse extends Affectation
{
    public function __construct(\App\Entity\Affectation $affectation)
    {
        $this->uuid = $affectation->getUuid();
        $this->statut = $affectation->getStatut();
        $this->dateAffectation = $affectation->getCreatedAt()->format(\DATE_ATOM);
        $this->dateAcceptation = $affectation->getAnsweredAt()->format(\DATE_ATOM);
        $this->motifRefus = $affectation->getMotifRefus();
        $this->motifCloture = $affectation->getMotifCloture();
    }
}
