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
            dump($detail);
            $details = $label;
            if ($detail instanceof Criticite && $detail->getLabel()) {
                $details .= \PHP_EOL.' - '.$detail->getLabel();
            } else {
                foreach ($detail as $desordrePrecision) {
                    if ($desordrePrecision instanceof DesordrePrecision && $desordrePrecision->getLabel()) {
                        $details .= \PHP_EOL.' - '.strip_tags($desordrePrecision->getLabel());
                    }
                }
            }
            $this->details[] = $details;
        }
    }
}
