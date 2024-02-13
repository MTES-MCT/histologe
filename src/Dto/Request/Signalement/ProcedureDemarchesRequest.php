<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class ProcedureDemarchesRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(['message' => 'Merci d\'indiquer si le bailleur a été averti.', 'groups' => ['LOCATAIRE']])]
        private readonly ?string $isProprioAverti = null,
        #[Assert\NotBlank([
            'message' => 'Merci d\'indiquer si l\'assurance a été contactée.',
            'groups' => ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR'], ])]
        private readonly ?string $infoProcedureAssuranceContactee = null,
        #[Assert\When(
            expression: 'this.getInfoProcedureAssuranceContactee() == "oui"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de noter la réponde de l\'assurance.'),
            ],
        )]
        private readonly ?string $infoProcedureReponseAssurance = null,
        #[Assert\NotBlank([
            'message' => 'Merci d\'indiquer si l\'occupant souhaite garder son logement après travaux.',
            'groups' => ['LOCATAIRE', 'BAILLEUR_OCCUPANT'], ])]
        private readonly ?string $infoProcedureDepartApresTravaux = null,
        private readonly ?string $preavisDepart = null,
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

    public function getPreavisDepart(): ?string
    {
        return $this->preavisDepart;
    }
}
