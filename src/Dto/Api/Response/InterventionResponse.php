<?php

namespace App\Dto\Api\Response;

use App\Entity\Intervention;

class InterventionResponse
{
    public string $dateIntervention;
    public ?string $type;
    public ?string $statut;
    public ?PartnerResponse $partner;
    public ?string $details;
    public array $conclusions = [];
    public ?bool $occupantPresent;
    public ?bool $proprietairePresent;

    public function __construct(
        Intervention $intervention,
    ) {
        $this->dateIntervention = $intervention->getScheduledAt()->format(\DATE_ATOM);
        $this->type = $intervention->getType()?->label();
        $this->statut = $intervention->getStatus();
        $this->partner = $intervention->getPartner() ? new PartnerResponse($intervention->getPartner()) : null;
        $this->details = $intervention->getDetails(); // traitement de suppression du html
        $this->conclusions = $intervention->getConcludeProcedure() ?? [];
        $this->occupantPresent = $intervention->isOccupantPresent();
        $this->proprietairePresent = $intervention->isProprietairePresent();
        // besoin d'exposer plus d'élements ?
    }
}
