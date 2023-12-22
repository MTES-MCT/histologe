<?php

namespace App\Service\Signalement\DesordreTraitement;

interface DesordreTraitementInterface
{
    public function findDesordresPrecisionsBy(array $payload, string $slug): array;
}
