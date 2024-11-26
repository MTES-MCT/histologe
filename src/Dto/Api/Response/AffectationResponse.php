<?php

namespace App\Dto\Api\Response;

use App\Entity\Affectation;

class AffectationResponse
{
    public string $dateCreation;
    public ?string $dateReponse;
    public int $statut;
    public PartnerResponse $partnerResponse;
    public ?string $motifCloture;
    public ?string $motifRefus;

    public function __construct(
        Affectation $affectation,
    ) {
        $this->dateCreation = $affectation->getCreatedAt()->format(\DATE_ATOM);
        $this->dateReponse = $affectation->getAnsweredAt()?->format(\DATE_ATOM);
        $this->statut = $affectation->getStatut(); // envoyer un libellé ?
        $this->partnerResponse = new PartnerResponse($affectation->getPartner());
        $this->motifCloture = $affectation->getMotifCloture()?->label();
        $this->motifRefus = $affectation->getMotifRefus()?->label();
    }
}
