<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class ProcedureDemarchesRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci d\'indiquer si le bailleur a été averti.', groups : ['LOCATAIRE'])]
        #[Assert\Choice(
            choices: ['0', '1'],
            message: 'Le champ "Propriétaire averti" est incorrect.',
        )]
        private readonly ?string $isProprioAverti = null,

        #[Assert\NotBlank(
            message: 'Merci d\'indiquer comment le bailleur a été averti.',
            groups: ['LOCATAIRE', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS']
        )] // TODO : à vérifier car obligatoire seulement si isProprioAverti = 1
        #[Assert\Choice(
            choices: ['courrier', 'email', 'telephone', 'sms', 'autre', 'nsp'],
            message: 'Le champ "infoProcedureBailMoyen" est incorrect.',
        )]
        private ?string $infoProcedureBailMoyen = null,

        #[Assert\NotBlank(
            message: 'Merci d\'indiquer comment le bailleur a été averti.',
            groups: ['LOCATAIRE']
        )] // TODO : à vérifier car obligatoire seulement si isProprioAverti = 1
        #[Assert\DateTime('Y-m')] // TODO : à vérifier
        private ?string $infoProcedureBailDate = null,

        #[Assert\NotBlank(
            message: 'Merci d\'indiquer comment le bailleur a été averti.',
            groups: ['LOCATAIRE']
        )] // TODO : à vérifier car obligatoire seulement si isProprioAverti = 1
        #[Assert\Length(
            max: 255, // TODO : à vérifier
            maxMessage: 'La réponse du bailleur ne doit pas dépasser {{ limit }} caractères.',
        )]
        private ?string $infoProcedureBailReponse = null,

        #[Assert\Length(
            max: 255, // TODO : à vérifier
            maxMessage: 'Le numéro de réclamation ne doit pas dépasser {{ limit }} caractères.',
        )]
        private ?string $infoProcedureBailNumero = null,

        #[Assert\NotBlank(
            message: 'Merci d\'indiquer si l\'assurance a été contactée.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'pas_assurance_logement'],
            message: 'Le champ "Assurance contactée" est incorrect.',
        )]
        private readonly ?string $infoProcedureAssuranceContactee = null,
        #[Assert\When(
            expression: 'this.getInfoProcedureAssuranceContactee() == "oui"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de noter la réponse de l\'assurance.'),
            ],
        )]
        #[Assert\Length(
            max: 255,
            maxMessage: 'La réponse de l\'assurance ne doit pas dépasser {{ limit }} caractères.',
        )]
        private readonly ?string $infoProcedureReponseAssurance = null,
        #[Assert\NotBlank(
            message : 'Merci d\'indiquer si l\'occupant souhaite garder son logement après travaux.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Souhaite garder le logement après travaux" est incorrect.',
        )]
        private readonly ?string $infoProcedureDepartApresTravaux = null,
        // TODO : ajouter les nouvelles lignes
        #[Assert\Length(max: 50)]
        private readonly ?string $preavisDepart = null,
    ) {
    }

    public function getIsProprioAverti(): ?string
    {
        return $this->isProprioAverti;
    }

    public function getinfoProcedureBailMoyen(): ?string
    {
        return $this->infoProcedureBailMoyen;
    }

    public function getinfoProcedureBailDate(): ?string
    {
        return $this->infoProcedureBailDate;
    }

    public function getinfoProcedureBailReponse(): ?string
    {
        return $this->infoProcedureBailReponse;
    }

    public function getinfoProcedureBailNumero(): ?string
    {
        return $this->infoProcedureBailNumero;
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
