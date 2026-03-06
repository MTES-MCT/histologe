<?php

namespace App\Service\Signalement\InputValue;

use App\Entity\Model\SituationFoyer;
use App\Entity\Signalement;

class SituationFoyerProcessor
{
    public function processIsLogementSocial(
        Signalement $signalement,
        ?string $isLogementSocial,
        ?SituationFoyer $situationFoyer = null,
    ): void {
        if ('nsp' === $isLogementSocial) {
            $signalement->setIsLogementSocial(null);
            if ($situationFoyer) {
                $situationFoyer->setLogementSocialAllocation(null);
            }
        } elseif ($isLogementSocial) {
            $signalement->setIsLogementSocial(true);
            if ($situationFoyer) {
                $situationFoyer->setLogementSocialAllocation('oui');
            }
        } else {
            $signalement->setIsLogementSocial(false);
            if ($situationFoyer) {
                $situationFoyer->setLogementSocialAllocation('non');
            }
        }
    }

    public function processIsAllocataire(Signalement $signalement, ?string $isAllocataire, ?string $caisseAllocation = null): void
    {
        $caisseAllocation = mb_strtolower($caisseAllocation ?? '');
        if ('non' === $isAllocataire) {
            $signalement->setIsAllocataire('0');
        } elseif (!empty($isAllocataire)) {
            if (in_array($caisseAllocation, ['caf', 'msa'], true)) {
                $signalement->setIsAllocataire($caisseAllocation);
            } else {
                $signalement->setIsAllocataire('1');
            }
        }
    }
}
