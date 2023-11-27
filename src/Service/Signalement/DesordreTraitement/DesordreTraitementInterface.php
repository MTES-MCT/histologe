<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use Doctrine\Common\Collections\ArrayCollection;

interface DesordreTraitementInterface
{
    public function process(DesordreCritere $critere, array $payload): ArrayCollection;
}
