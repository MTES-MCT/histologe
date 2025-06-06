<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;

interface DesordreTraitementInterface
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, DesordrePrecision>
     */
    public function findDesordresPrecisionsBy(array $payload, string $slug): array;
}
