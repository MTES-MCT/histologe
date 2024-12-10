<?php

namespace App\Service\BetaGouv\Response;

class RnbBuilding
{
    private ?string $rnbId = null;

    public function __construct(?array $building = null)
    {
        $this->rnbId = $building['rnb_id'];
    }

    public function getRnbId(): ?string
    {
        return $this->rnbId;
    }
}
