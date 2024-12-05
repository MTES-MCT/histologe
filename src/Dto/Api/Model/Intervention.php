<?php

namespace App\Dto\Api\Model;

use App\Entity\Intervention as InterventionEntity;

class Intervention
{
    public string $dateIntervention;
    public ?string $type;
    public ?string $statut;
    public ?Partner $partner;
    public ?string $details;
    public array $conclusions = [];
    public ?bool $occupantPresent;
    public ?bool $proprietairePresent;

    public function __construct(
        InterventionEntity $intervention,
    ) {
        $this->dateIntervention = $intervention->getScheduledAt()->format(\DATE_ATOM);
        $this->type = $intervention->getType()?->label();
        $this->statut = $intervention->getStatus();
        $this->partner = $intervention->getPartner() ? new Partner($intervention->getPartner()) : null;
        $this->details = $intervention->getDetails(); // traitement de suppression du html
        $this->conclusions = $intervention->getConcludeProcedure() ?? [];
        $this->occupantPresent = $intervention->isOccupantPresent();
        $this->proprietairePresent = $intervention->isProprietairePresent();
    }
}
