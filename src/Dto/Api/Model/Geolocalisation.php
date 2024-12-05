<?php

namespace App\Dto\Api\Model;

class Geolocalisation
{
    public function __construct(public ?float $latitude, public ?float $longitude)
    {
    }
}
