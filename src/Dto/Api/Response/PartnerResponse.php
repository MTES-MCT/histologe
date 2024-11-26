<?php

namespace App\Dto\Api\Response;

use App\Entity\Partner;

class PartnerResponse
{
    public string $nom;
    public ?string $type;
    public array $competences = [];

    public function __construct(
        Partner $partner,
    ) {
        $this->nom = $partner->getNom();
        $this->type = $partner->getType()?->label();
        $this->competences = $partner->getCompetence() ?? [];
        // besoin d'exposer plus d'élements ?
    }
}
