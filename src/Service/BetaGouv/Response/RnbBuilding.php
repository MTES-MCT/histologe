<?php

namespace App\Service\BetaGouv\Response;

class RnbBuilding
{
    private ?string $rnbId = null;
    private ?float $lat = null;
    private ?float $lng = null;

    public function __construct(?array $building = null)
    {
        $this->rnbId = $building['rnb_id'];
        $this->lng = $building['point']['coordinates'][0];
        $this->lat = $building['point']['coordinates'][1];
    }

    public function getRnbId(): ?string
    {
        return $this->rnbId;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }
}
