<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CompositionLogementRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de définir le type de logement.')]
        #[Assert\Choice(
            choices: ['maison', 'appartement', 'autre'],
            message: 'Le type de logement est incorrect.'
        )]
        private readonly ?string $type = null,
        #[Assert\When(
            expression: 'this.getType() == "autre"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser le type de logement autre.'),
            ],
        )]
        #[Assert\Length(
            max: 100,
            maxMessage: 'Le type de logement autre ne doit pas dépasser {{ limit }} caractères.',
        )]
        private readonly ?string $typeLogementNatureAutrePrecision = null,
        #[Assert\NotBlank(
            message: 'Merci de sélectioner pièce unique ou plusieurs pièces.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS']
        )]
        #[Assert\Choice(
            options: ['piece_unique', 'plusieurs_pieces'],
            message: 'Merci de sélectionner pièce unique ou plusieurs pièces.')]
        private readonly ?string $typeCompositionLogement = null,
        #[Assert\NotBlank(
            message: 'Merci de saisir la superficie du logement.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'])]
        #[Assert\Positive(message: 'Merci de saisir une information numérique dans le champs de superficie.')]
        #[Assert\Type(type: 'numeric', message: 'La superficie doit être un nombre.')]
        #[Assert\Length(
            max: 10,
            maxMessage: 'La superficie ne doit pas dépasser {{ limit }} caractères.',
        )]
        private readonly ?string $superficie = null,
        #[Assert\NotBlank(
            message: 'Merci de définir la hauteur du logement.',
            groups: [
                'LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS', ]
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Hauteur > 2m" est incorrecte.'
        )]
        private readonly ?string $compositionLogementHauteur = null,
        #[Assert\NotBlank(
            message: 'Merci de définir le nombre de pièces à vivre.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS']
        )]
        #[Assert\Positive(message: 'Merci de saisir une information numérique dans le champs nombre de pièces à vivre.')]
        #[Assert\Type(type: 'numeric', message: 'Le nombre de pièces à vivre doit être un nombre.')]
        #[Assert\Length(
            max: 10,
            maxMessage: 'Le nombre de pièces à vivre ne doit pas dépasser {{ limit }} caractères.',
        )]
        private readonly ?string $compositionLogementNbPieces = null,
        #[Assert\Type(type: 'numeric', message: 'Le nombre d\'étages doit être un nombre.')]
        #[Assert\Length(
            max: 10,
            maxMessage: 'Le nombre d\'étages ne doit pas dépasser {{ limit }} caractères.',
        )]
        private readonly ?string $nombreEtages = null,
        #[Assert\When(
            expression: 'this.getType() == "appartement"',
            groups: [
                'LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS',
            ],
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser si le logement est au rez-de-chaussée.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Rez-de-chaussée" est incorrect.'
        )]
        private readonly ?string $typeLogementRdc = null,
        #[Assert\When(
            expression: 'this.getType() == "appartement" && this.getTypeLogementRdc() == "non"',
            groups: [
                'LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS',
            ],
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser si le logement est au dernier étage.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Dernier étage" est incorrect.'
        )]
        private readonly ?string $typeLogementDernierEtage = null,
        #[Assert\When(
            expression: 'this.getType() == "appartement" && this.getTypeLogementDernierEtage() == "oui"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser si le logement est sous comble et sans fenêtre.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Sous comble et sans fenêtre" est incorrect.'
        )]
        private readonly ?string $typeLogementSousCombleSansFenetre = null,
        #[Assert\When(
            expression: 'this.getType() == "appartement" && this.getTypeLogementRdc() == "non" && this.getTypeLogementDernierEtage() == "non"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser si le logement est au sous-sol et sans fenêtre.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Sous-sol et sans fenêtre" est incorrect.'
        )]
        private readonly ?string $typeLogementSousSolSansFenetre = null,
        #[Assert\NotBlank(
            message: 'Merci de définir si une pièce fait plus de 9m².',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER'])]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Pièce à vivre > 9m²" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesPieceAVivre9m = null,
        #[Assert\NotBlank(
            message: 'Merci de définir si il y a une cuisine.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Cuisine" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesCuisine = null,
        #[Assert\When(
            expression: 'this.getTypeLogementCommoditesCuisine() == "non"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser s\'il y a une cuisine collective.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "Cuisine collective" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesCuisineCollective = null,
        #[Assert\NotBlank(
            message: 'Merci de définir si il y a une salle de bain.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "Salle de bain" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesSalleDeBain = null,
        #[Assert\When(
            expression: 'this.getTypeLogementCommoditesSalleDeBain() == "non"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser s\'il y a une salle de bain collective.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "Salle de bain collective" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesSalleDeBainCollective = null,
        #[Assert\NotBlank(
            message: 'Merci de définir si il y a des WC.',
            groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT', 'BAILLEUR', 'TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS']
        )]
        #[Assert\Choice(
            choices: ['oui', 'non', 'nsp'],
            message: 'Le champ "WC" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesWc = null,
        #[Assert\When(
            expression: 'this.getTypeLogementCommoditesWc() == "non"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser s\'il y a des wc collectifs.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "WC collectifs" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesWcCollective = null,
        #[Assert\When(
            expression: 'this.getTypeLogementCommoditesWc() == "oui" && this.getTypeLogementCommoditesCuisine() == "oui"',
            constraints: [
                new Assert\NotBlank(message: 'Merci de préciser s\'il y a des wc dans la cuisine.'),
            ],
        )]
        #[Assert\Choice(
            choices: ['oui', 'non'],
            message: 'Le champ "WC dans la cuisine" est incorrect.'
        )]
        private readonly ?string $typeLogementCommoditesWcCuisine = null,
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTypeLogementNatureAutrePrecision(): ?string
    {
        return $this->typeLogementNatureAutrePrecision;
    }

    public function getTypeCompositionLogement(): ?string
    {
        return $this->typeCompositionLogement;
    }

    public function getSuperficie(): ?string
    {
        return $this->superficie;
    }

    public function getCompositionLogementHauteur(): ?string
    {
        return $this->compositionLogementHauteur;
    }

    public function getCompositionLogementNbPieces(): ?string
    {
        return $this->compositionLogementNbPieces;
    }

    public function getNombreEtages(): ?string
    {
        return $this->nombreEtages;
    }

    public function getTypeLogementRdc(): ?string
    {
        return $this->typeLogementRdc;
    }

    public function getTypeLogementDernierEtage(): ?string
    {
        return $this->typeLogementDernierEtage;
    }

    public function getTypeLogementSousCombleSansFenetre(): ?string
    {
        return $this->typeLogementSousCombleSansFenetre;
    }

    public function getTypeLogementSousSolSansFenetre(): ?string
    {
        return $this->typeLogementSousSolSansFenetre;
    }

    public function getTypeLogementCommoditesPieceAVivre9m(): ?string
    {
        return $this->typeLogementCommoditesPieceAVivre9m;
    }

    public function getTypeLogementCommoditesCuisine(): ?string
    {
        return $this->typeLogementCommoditesCuisine;
    }

    public function getTypeLogementCommoditesCuisineCollective(): ?string
    {
        return $this->typeLogementCommoditesCuisineCollective;
    }

    public function getTypeLogementCommoditesSalleDeBain(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBain;
    }

    public function getTypeLogementCommoditesSalleDeBainCollective(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBainCollective;
    }

    public function getTypeLogementCommoditesWc(): ?string
    {
        return $this->typeLogementCommoditesWc;
    }

    public function getTypeLogementCommoditesWcCollective(): ?string
    {
        return $this->typeLogementCommoditesWcCollective;
    }

    public function getTypeLogementCommoditesWcCuisine(): ?string
    {
        return $this->typeLogementCommoditesWcCuisine;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ('oui' === $this->getTypeLogementDernierEtage() && 'oui' === $this->getTypeLogementRdc()) {
            $context->buildViolation('Merci de bien préciser si le logement est au rez-de-chaussée ou dernier étage.')
                ->atPath('typeLogementRdc')
                ->addViolation();

            $context->buildViolation('Merci de bien préciser si le logement est au rez-de-chaussée ou dernier étage.')
                ->atPath('typeLogementDernierEtage')
                ->addViolation();
        }
    }
}
