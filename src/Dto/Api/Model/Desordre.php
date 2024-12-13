<?php

namespace App\Dto\Api\Model;

use App\Entity\Criticite;
use App\Entity\DesordrePrecision;

class Desordre
{
    public string $categorie;
    public ?string $zone;
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
            if ($detail->getLabel() && ($detail instanceof DesordrePrecision || $detail instanceof Criticite)) {
                $details .= ' : '.$detail->getLabel();
            }
            $this->details[] = $details;
        }
    }
}
