<?php

namespace App\Dto\Api\Model;

use App\Entity\Partner as PartnerEntity;

class Partner
{
    public string $nom;
    public ?string $type;
    public array $competences = [];

    public function __construct(
        PartnerEntity $partner,
    ) {
        $this->nom = $partner->getNom();
        $this->type = $partner->getType()?->label();
        $this->competences = $partner->getCompetence() ?? [];
    }
}
