<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class ProcedureDemarchesRequest
{
    public function __construct(
        #[Assert\NotBlank(['message' => 'Veuillez définir le champ bailleur averti', 'groups' => ['LOCATAIRE']])]
        private readonly ?string $isProprioAverti = null,
        #[Assert\NotBlank(['message' => 'Veuillez définir le champ contact assurance', 'groups' => ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR']])]
        private readonly ?string $infoProcedureAssuranceContactee = null,
        private readonly ?string $infoProcedureReponseAssurance = null,
        #[Assert\NotBlank(['message' => 'Veuillez définir le champ garder logement aprés travaux', 'groups' => ['LOCATAIRE', 'BAILLEUR_OCCUPANT']])]
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
