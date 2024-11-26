<?php

namespace App\Dto\Api\Response;

use App\Entity\Criticite;
use App\Entity\DesordrePrecision;

class DesordreResponse
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
        foreach ($data as $label => $unused) {
            $details = $label;
            if ($unused instanceof DesordrePrecision && $unused->getLabel()) {
                $details .= ' : '.$unused->getLabel();
            } elseif ($unused instanceof Criticite && $unused->getLabel()) {
                $details .= ' : '.$unused->getLabel();
            }
            $this->details[] = $details;
        }
    }
}
