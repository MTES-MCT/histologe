<?php

namespace App\Dto\Request\Signalement;

class ProcedureDemarchesRequest
{
    public function __construct(
        private readonly ?string $isProprioAverti = null,
        private readonly ?string $infoProcedureAssuranceContactee = null,
        private readonly ?string $infoProcedureReponseAssurance = null,
        private readonly ?string $infoProcedureDepartApresTravaux = null,
    ) {
    }

    public function getIsProprioAverti(): ?string
    {
        return $this->isProprioAverti;
    }

    public function getInfoProcedureAssuranceContactee(): ?string
    {
        return $this->infoProcedureAssuranceContactee;
    }

    public function getInfoProcedureReponseAssurance(): ?string
    {
        return $this->infoProcedureReponseAssurance;
    }

    public function getInfoProcedureDepartApresTravaux(): ?string
    {
        return $this->infoProcedureDepartApresTravaux;
    }
}
