<?php

namespace App\Dto\Api\Request;

use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    description: 'Payload de création d\'un signalement.',
    required: ['adresseOccupant', 'codePostalOccupant', 'communeOccupant', 'profilDeclarant', 'isLogementSocial'],
)]
#[Groups(groups: ['Default', 'false'])]
class SignalementRequest implements RequestInterface
{
    #[OA\Property(
        description: 'Identifiant UUID du partenaire pour lequel le signalement est créé.
                    <br><strong>Obligatoire si vous avez les permissions sur plusieurs partenaires.</strong>',
        example: '342bf101-506d-4159-ba0c-c097f8cf12e7',
    )]
    #[Assert\Uuid(message: 'Veuillez fournir un UUID de partenaire valide.')]
    public ?string $partenaireUuid = null;

    #[OA\Property(
        description: 'Adresse du logement (numéro et voie).',
        example: '151 chemin de la route',
    )]
    #[Assert\NotBlank(message: 'Veuillez renseigner l\'adresse du logement.')]
    #[Assert\Length(max: 100)]
    public ?string $adresseOccupant = null;

    #[OA\Property(
        description: 'Code postal du logement.',
        example: '34090',
    )]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
    public ?string $codePostalOccupant = null;

    #[OA\Property(
        description: 'Commune du logement.',
        example: 'Montpellier',
    )]
    #[Assert\NotBlank(message: 'Veuillez renseigner la commune du logement.')]
    #[Assert\Length(max: 100)]
    public ?string $communeOccupant = null;

    #[OA\Property(
        description: 'Etage du logement.',
        example: '2',
    )]
    #[Assert\Length(max: 5)]
    public ?string $etageOccupant = null;

    #[OA\Property(
        description: 'Escalier du logement.',
        example: 'B',
    )]
    #[Assert\Length(max: 3)]
    public ?string $escalierOccupant = null;

    #[OA\Property(
        description: 'Numéro d\'appartement du logement.',
        example: '24B',
    )]
    #[Assert\Length(max: 5)]
    public ?string $numAppartOccupant = null;

    #[OA\Property(
        description: 'Complément d\'adresse du logement.',
        example: 'Résidence les oliviers',
    )]
    #[Assert\Length(max: 255)]
    public ?string $adresseAutreOccupant = null;

    #[OA\Property(
        description: 'Profil du déclarant.',
        example: ProfileDeclarant::LOCATAIRE->value,
    )]
    #[Assert\NotBlank(message: 'Veuillez renseigner le profil du déclarant.')]
    #[Assert\Choice(
        choices: [
            ProfileDeclarant::TIERS_PARTICULIER->value,
            ProfileDeclarant::TIERS_PRO->value,
            ProfileDeclarant::SERVICE_SECOURS->value,
            ProfileDeclarant::BAILLEUR->value,
            ProfileDeclarant::BAILLEUR_OCCUPANT->value,
            ProfileDeclarant::LOCATAIRE->value,
        ],
        message: 'Cette valeur doit être l\'un des choix suivants : {{ choices }}'
    )]
    public ?string $profilDeclarant = null;

    #[OA\Property(
        description: 'Lien entre le déclarant et l\'occupant.
                      <br>A renseigner uniquement dans le cas du profilDeclarant '.ProfileDeclarant::TIERS_PARTICULIER->value.'.',
        example: OccupantLink::PROCHE->value,
    )]
    #[Assert\Choice(
        choices: [
            OccupantLink::PROCHE->value,
            OccupantLink::VOISIN->value,
            OccupantLink::AUTRE->value,
        ],
        message: 'Cette valeur doit être l\'un des choix suivants : {{ choices }}'
    )]
    public ?string $lienDeclarantOccupant = null;

    #[OA\Property(
        description: 'S\'agit-il d\'un logement social ?',
        example: false,
    )]
    #[Assert\NotNull(message: 'Veuillez indiquer si le logement est un logement social ou non.')]
    public ?bool $isLogementSocial = null;

    #[OA\Property(
        description: 'S\'agit-il d\'un logement vacant ?
                    <br>Sera toujours mis à false pour les profils "LOCATAIRE" et "BAILLEUR_OCCUPANT".',
        example: false,
    )]
    public ?bool $isLogementVacant = null;

    #[OA\Property(
        description: 'Nombre d\'occupants dans le logement.',
        example: 4,
    )]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Assert\LessThan(value: 100)]
    public ?int $nbOccupantsLogement = null;

    #[OA\Property(
        description: 'Nombre d\'enfants dans le logement.',
        example: 2,
    )]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'Veuillez saisir un nombre entier.')]
    #[Assert\LessThan(value: 100, message: 'Le nombre d\'enfants doit être inférieur à 100.')]
    #[Assert\LessThanOrEqual(propertyPath: 'nbOccupantsLogement', message: 'Le nombre d\'enfants ne peut pas être supérieur au nombre d\'occupants.')]
    public ?int $nbEnfantsDansLogement = null;

    #[OA\Property(
        description: 'Y a-t-il des enfants de moins de 6 ans dans le logement ?',
        example: true,
    )]
    public ?bool $isEnfantsMoinsSixAnsDansLogement = null;

    // TAB addresse du form BO ok

    // TODO TAB logement
    // TODO TAB situation
    // TODO TAB cordonnées
    // TODO TAB désordres
    // TODO TAB validation ?
}
