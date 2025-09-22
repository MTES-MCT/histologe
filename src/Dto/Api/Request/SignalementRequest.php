<?php

namespace App\Dto\Api\Request;

use App\Entity\Enum\ChauffageType;
use App\Entity\Enum\EtageType;
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
                    <br><strong>⚠️Obligatoire si vous avez les permissions sur plusieurs partenaires.</strong>',
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
    )]
    public ?string $profilDeclarant = null;

    #[OA\Property(
        description: 'Lien entre le déclarant et l\'occupant.
                      <br>⚠️Pris en compte uniquement dans le cas du profilDeclarant '.ProfileDeclarant::TIERS_PARTICULIER->value.'.',
        example: OccupantLink::PROCHE->value,
    )]
    #[Assert\Choice(
        choices: [
            OccupantLink::PROCHE->value,
            OccupantLink::VOISIN->value,
            OccupantLink::AUTRE->value,
        ],
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
                    <br>⚠️Pris en compte uniquement pour les profilDeclarant "LOCATAIRE" et "BAILLEUR_OCCUPANT".',
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

    #[OA\Property(
        description: 'Nature du logement.',
        example: 'appartement',
    )]
    #[Assert\Choice(
        choices: [
            'appartement',
            'maison',
            'autre',
        ],
    )]
    public ?string $natureLogement = null;

    #[OA\Property(
        description: 'Précision sur la nature du logement.
                    <br>⚠️Pris en compte uniquement dans le cas où natureLogement = "autre".',
        example: 'caravane',
    )]
    public ?string $natureLogementAutre = null;

    #[OA\Property(
        description: 'Spécificité de l\'étage de l\'appartement.
                    <br>⚠️Pris en compte uniquement dans le cas où natureLogement = "appartement".',
        example: EtageType::RDC->value,
    )]
    #[Assert\Choice(
        choices: [
            EtageType::RDC->value,
            EtageType::DERNIER_ETAGE->value,
            EtageType::SOUSSOL->value,
            EtageType::AUTRE->value,
        ],
    )]
    public ?string $etageAppartement = null;

    #[OA\Property(
        description: 'L\'appartement dispose-t-il de fenêtres ?
                    <br>⚠️Pris en compte uniquement dans le cas où natureLogement = "appartement".',
        example: true,
    )]
    public ?bool $isAppartementAvecFenetres = null;

    #[OA\Property(
        description: 'Nombre d\'étages dans le logement.',
        example: 0,
    )]
    public ?int $nombreEtages = null;

    #[OA\Property(
        description: 'Année de construction du logement.',
        example: 1970,
    )]
    public ?int $anneeConstruction = null;
    #[OA\Property(
        description: 'Nombre de pièces à vivre dans le logement (salon, chambre).',
        example: 4,
    )]
    public ?int $nombrePieces = null;
    #[OA\Property(
        description: 'Superficie du logement en m².',
        example: 85.5,
    )]
    public ?float $superficie = null;

    #[OA\Property(
        description: 'Le logement dispose-t-il d\'au moins une pièce à vivre de 9m² ou plus ?',
        example: true,
    )]
    public ?bool $isPieceAVivre9m = null;

    #[OA\Property(
        description: 'Le logement dispose-t-il d\'une cuisine ou un coin cuisine ?',
        example: true,
    )]
    public ?bool $isCuisine = null;

    #[OA\Property(
        description: 'Existe t-il un accès à une cuisine collective ?
                     <br>⚠️Pris en compte uniquement dans le cas où isCuisine = false.',
        example: false,
    )]
    public ?bool $isCuisineCollective = null;

    #[OA\Property(
        description: 'Le logement dispose-t-il d\'une salle de bain (salle d\'eau avec douche ou baignoire) ?',
        example: true,
    )]
    public ?bool $isSdb = null;

    #[OA\Property(
        description: 'Existe t-il un accès à une salle de bain collective ?
                     <br>⚠️Pris en compte uniquement dans le cas où isSdb = false.',
        example: false,
    )]
    public ?bool $isSdbCollective = null;

    #[OA\Property(
        description: 'Le logement dispose-t-il de WC ?',
        example: true,
    )]
    public ?bool $isWc = null;
    #[OA\Property(
        description: 'Existe t-il un accès à des WC collectifs ?
                     <br>⚠️Pris en compte uniquement dans le cas où isWc = false.',
        example: false,
    )]
    public ?bool $isWcCollectif = null;

    #[OA\Property(
        description: 'Les WC et la cuisine sont-ils dans la même pièce ?
                     <br>⚠️Pris en compte uniquement dans le cas où isWc = true et isCuisine = true.',
        example: false,
    )]
    public ?bool $isWcCuisineMemePiece = null;
    // typeChauffage

    #[OA\Property(
        description: 'Type de chauffage du logement.',
        example: ChauffageType::ELECTRIQUE->value,
    )]
    #[Assert\Choice(
        choices: [
            ChauffageType::ELECTRIQUE->value,
            ChauffageType::GAZ->value,
            ChauffageType::AUCUN->value,
            ChauffageType::NSP->value,
        ],
    )]
    public ?string $typeChauffage = null;

    // TODO TAB situation
    // TODO TAB cordonnées
    // TODO TAB désordres
    // TODO TAB validation ?
}
