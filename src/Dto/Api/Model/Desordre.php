<?php

namespace App\Dto\Api\Model;

use App\Entity\Criticite;
use App\Entity\DesordrePrecision;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Desordre',
    description: 'Schéma représentant les désordres identifiés dans le logement.'
)]
class Desordre
{
    #[OA\Property(
        description: 'Catégorie du désordre.',
        example: 'Eau potable / assainissement'
    )]
    public string $categorie;
    #[OA\Property(
        description: 'Zone exacte du logement ou du bâtiment où le désordre est constaté.
        Les choix possibles sont : `BATIMENT`, `LOGEMENT`.',
        example: 'LOGEMENT',
        nullable: true
    )]
    public ?string $zone;
    #[OA\Property(
        description: 'Liste des observations détaillées associées au désordre.',
        example: [
            "Il n'y a pas d'eau potable dans le logement",
            "L'évacuation des eaux ne marche pas (mauvaises odeurs, pas d'évacuation, fuites...)",
            "Il n'y a pas d'eau chaude dans le logement",
        ],
        nullable: true
    )]
    public array $details = [];

    public function __construct(
        string $categorie,
        array $data,
        ?string $zone = null,
    ) {
        $this->categorie = $categorie;
        $this->zone = $zone;
        foreach ($data as $label => $detail) {
            $details = $label;
            if ($detail instanceof Criticite && $detail->getLabel()) {
                $details .= \PHP_EOL.' - '.$detail->getLabel();
            } else {
                foreach ($detail as $desordrePrecision) {
                    if ($desordrePrecision instanceof DesordrePrecision && $desordrePrecision->getLabel()) {
                        $details .= \PHP_EOL.' - '.strip_tags(str_replace('<br>', \PHP_EOL, $desordrePrecision->getLabel()));
                    }
                }
            }
            $this->details[] = $details;
        }
    }
}
