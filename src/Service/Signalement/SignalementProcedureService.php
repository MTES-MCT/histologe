<?php

namespace App\Service\Signalement;

use App\Entity\Enum\ProcedureCategory;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Entity\Signalement;

class SignalementProcedureService
{
    /** @return list<ProcedureType> */
    public function getProceduresPreselectedForClosing(Signalement $signalement): array
    {
        $procedures = [];
        // procédures en provenance des conclusion de visites
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
        // procédures à engager
        foreach ($signalement->getSignalementProcedures(ProcedureCategory::PROCEDURE_ENGAGEE) as $signalementProcedure) {
            $procedure = $signalementProcedure->getProcedureType();
            $procedures[$procedure->value] = $procedure;
        }

        return array_values($procedures);
    }
}
