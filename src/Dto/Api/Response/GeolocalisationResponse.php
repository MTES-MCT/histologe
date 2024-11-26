<?php

namespace App\Dto\Api\Response;

class GeolocalisationResponse
{
    public function __construct(public ?float $latitude, public ?float $longitude)
    {
    }
}
