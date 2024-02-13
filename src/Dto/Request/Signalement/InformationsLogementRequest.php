<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class InformationsLogementRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de définir le nombre de personnes.')]
        private readonly ?string $nombrePersonnes = null,
        #[Assert\NotBlank(
            message: 'Merci de définir le nombre d\'enfants.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'TIERS_PARTICULIER', 'TIERS_PRO', 'BAILLEUR'])]
        private readonly ?string $compositionLogementEnfants = null,
        #[Assert\NotBlank(message: 'Merci de définir la date d\'arrivée.', groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $dateEntree = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $bailleurDateEffetBail = null,
        #[Assert\NotBlank(message: 'Merci de définir le bail.', groups: ['LOCATAIRE', 'BAILLEUR'])]
        private readonly ?string $bailDpeBail = null,
        #[Assert\NotBlank(message: 'Merci de définir l\'état des lieux.', groups: ['LOCATAIRE', 'BAILLEUR'])]
        private readonly ?string $bailDpeEtatDesLieux = null,
        #[Assert\NotBlank(message: 'Merci de définir le DPE.', groups: ['LOCATAIRE', 'BAILLEUR', 'BAILLEUR_OCCUPANT'])]
        private readonly ?string $bailDpeDpe = null,
        private readonly ?string $loyer = null,
        private readonly ?string $loyersPayes = null,
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
