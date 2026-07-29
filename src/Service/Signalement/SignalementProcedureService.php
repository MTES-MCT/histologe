<?php

namespace App\Service\Signalement;

use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Entity\Signalement;

class SignalementProcedureService
{
    /** @return list<ProcedureType> */
    public function getProceduresFromIntervention(Signalement $signalement): array
    {
        $procedures = [];
        foreach ($signalement->getInterventions() as $intervention) {
            if (Intervention::STATUS_DONE !== $intervention->getStatus()) {
                continue;
            }
            foreach ($intervention->getConcludeProcedure() as $procedure) {
                if (in_array($procedure, [ProcedureType::LOGEMENT_DECENT, ProcedureType::RESPONSABILITE_OCCUPANT_ASSURANTIEL], true)) {
                    continue;
                }
                if (ProcedureType::SUSPICION_INSALUBRE === $procedure) {
                    $procedure = ProcedureType::INSALUBRITE;
                }
                $procedures[$procedure->value] = $procedure;
            }
        }

        return array_values($procedures);
    }
}
