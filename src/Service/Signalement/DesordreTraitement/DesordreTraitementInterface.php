<?php

namespace App\Service\Signalement\DesordreTraitement;

use Doctrine\Common\Collections\ArrayCollection;

interface DesordreTraitementInterface
{
    public function process(array $payload, string $slug): ArrayCollection;
}
