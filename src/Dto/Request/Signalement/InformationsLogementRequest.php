<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class InformationsLogementRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de définir le nombre de personnes.')]
        #[Assert\Positive(message: 'Le nombre de personnes doit être un nombre positif.')]
        #[Assert\Type(type: 'numeric', message: 'Le nombre de personnes doit être un nombre.')]
        private readonly ?string $nombrePersonnes = null,
        #[Assert\NotBlank(
            message: 'Merci de définir si il y a des enfants de moins de 6 ans',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO', 'BAILLEUR'])]
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "Enfants -6 ans" est incorrect.')]
        private readonly ?string $compositionLogementEnfants = null,
        #[Assert\NotBlank(message: 'Merci de définir la date d\'arrivée.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $dateEntree = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $bailleurDateEffetBail = null,
        #[Assert\NotBlank(message: 'Merci d\'indiquer si un bail existe (ou a été fourni).', groups: ['LOCATAIRE', 'BAILLEUR'])]
        #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "bail" est incorrect.')]
        private readonly ?string $bailDpeBail = null,
        #[Assert\NotBlank(message: 'Merci d\'indiquer si un état des lieux existe (ou a été fourni).', groups: ['LOCATAIRE', 'BAILLEUR'])]
        #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "état des lieux" est incorrect.')]
        private readonly ?string $bailDpeEtatDesLieux = null,
        #[Assert\NotBlank(message: 'Merci d\'indiquer si un DPE existe (ou a été fourni).', groups: ['LOCATAIRE', 'BAILLEUR', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "DPE" est incorrect.')]
        private readonly ?string $bailDpeDpe = null,
        #[Assert\Type(type: 'numeric', message: 'Le loyer doit être un nombre.')]
        #[Assert\Length(max: 20, maxMessage: 'Le loyer ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $loyer = null,
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "Paiement loyers à jour" est incorrect.')]
        private readonly ?string $loyersPayes = null,
        #[Assert\Regex(pattern: '/^[0-9]{4}$/', message: 'L\'année de construction doit être composée de 4 chiffres.')]
        private readonly ?string $anneeConstruction = null,
    ) {
    }

    public function getNombrePersonnes(): ?string
    {
        return $this->nombrePersonnes;
    }

    public function getCompositionLogementEnfants(): ?string
    {
        return $this->compositionLogementEnfants;
    }

    public function getDateEntree(): ?string
    {
        return $this->dateEntree;
    }

    public function getBailleurDateEffetBail(): ?string
    {
        return $this->bailleurDateEffetBail;
    }

    public function getBailDpeBail(): ?string
    {
        return $this->bailDpeBail;
    }

    public function getBailDpeEtatDesLieux(): ?string
    {
        return $this->bailDpeEtatDesLieux;
    }

    public function getBailDpeDpe(): ?string
    {
        return $this->bailDpeDpe;
    }

    public function getLoyer(): ?string
    {
        return $this->loyer;
    }

    public function getLoyersPayes(): ?string
    {
        return $this->loyersPayes;
    }

    public function getAnneeConstruction(): ?string
    {
        return $this->anneeConstruction;
    }
}
