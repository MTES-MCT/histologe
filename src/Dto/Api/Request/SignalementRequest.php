<?php

namespace App\Dto\Api\Request;

use App\Entity\Enum\ChauffageType;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Validator as AppAssert;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    description: 'Payload de création d\'un signalement.',
    required: ['adresseOccupant', 'codePostalOccupant', 'communeOccupant', 'profilDeclarant', 'isLogementSocial', 'desordres'],
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
        example: '151 avenue du pont trinquat',
    )]
    #[Assert\NotBlank(message: 'Veuillez renseigner l\'adresse du logement.')]
    #[Assert\Length(max: 100)]
    public ?string $adresseOccupant = null;

    #[OA\Property(
        description: 'Code postal du logement.',
        example: '34070',
    )]
    #[Assert\NotBlank(message: 'Veuillez renseigner le code postal du logement.')]
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
                    <br>⚠️Pris en compte uniquement pour les profilDeclarant "TIERS_PARTICULIER", "TIERS_PRO", "SERVICE_SECOURS" et "BAILLEUR".',
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
    #[Assert\Length(max: 100)]
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
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Assert\LessThan(value: 100)]
    public ?int $nombreEtages = null;

    #[OA\Property(
        description: 'Année de construction du logement.',
        example: 1970,
    )]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'Veuillez saisir une année sur 4 chiffres.')]
    public ?int $anneeConstruction = null;
    #[OA\Property(
        description: 'Nombre de pièces à vivre dans le logement (salon, chambre).',
        example: 4,
    )]
    #[Assert\GreaterThan(value: 0)]
    #[Assert\LessThan(value: 100)]
    public ?int $nombrePieces = null;
    #[OA\Property(
        description: 'Superficie du logement en m².',
        example: 85.5,
    )]
    #[Assert\GreaterThan(value: 0)]
    #[Assert\LessThan(value: 10000)]
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
    public ?bool $isSalleDeBain = null;

    #[OA\Property(
        description: 'Existe t-il un accès à une salle de bain collective ?
                     <br>⚠️Pris en compte uniquement dans le cas où isSalleDeBain = false.',
        example: false,
    )]
    public ?bool $isSalleDeBainCollective = null;

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

    #[OA\Property(
        description: 'Existe-il un contrat de location (bail) ?',
        example: true,
    )]
    public ?bool $isBail = null;

    #[OA\Property(
        description: 'Existe-il un diagnostic de performance énergétique (DPE) ?',
        example: true,
    )]
    public ?bool $isDpe = null;

    #[OA\Property(
        description: 'Année du diagnostic de performance énergétique (DPE).',
        example: '2021',
    )]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'Veuillez saisir une année sur 4 chiffres.')]
    public ?string $anneeDpe = null;

    #[OA\Property(
        description: 'Classe énergétique du logement.',
        example: 'D',
    )]
    #[Assert\Choice(
        choices: [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
        ],
    )]
    public ?string $classeEnergetique = null;

    #[OA\Property(
        description: 'Existe-t-il un état des lieux ?',
        example: true,
    )]
    public ?bool $isEtatDesLieux = null;

    #[OA\Property(
        description: 'Date d\'entrée dans le logement au format AAAA-MM-DD.',
        example: '2023-01-31',
    )]
    #[Assert\Date(message: 'Veuillez saisir une date au format AAAA-MM-DD.')]
    public ?string $dateEntreeLogement = null;

    #[OA\Property(
        description: 'Montant du loyer.',
        example: 765.50,
    )]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Assert\LessThan(value: 10000)]
    public ?float $montantLoyer = null;

    #[OA\Property(
        description: 'Le paiement des loyers est-il à jour ?',
        example: true,
    )]
    public ?bool $isPaiementLoyersAJour = null;

    #[OA\Property(
        description: 'L\'occupant est-il allocataire CAF ou MSA ?',
        example: true,
    )]
    public ?bool $isAllocataire = null;

    #[OA\Property(
        description: 'Nom de la caisse d\'allocation de l\'allocataire.
                     <br>⚠️Pris en compte uniquement dans le cas où isAllocataire = true.',
        example: 'CAF',
    )]
    #[Assert\Choice(
        choices: [
            'CAF',
            'MSA',
        ],
    )]
    public ?string $caisseAllocations = null;

    #[OA\Property(
        description: 'Date de naissance de l\'allocataire au format AAAA-MM-DD.
                     <br>⚠️Pris en compte uniquement dans le cas où isAllocataire = true.',
        example: '2001-03-15',
    )]
    #[Assert\Date(message: 'Veuillez saisir une date au format AAAA-MM-DD.')]
    public ?string $dateNaissanceAllocataire = null;
    #[OA\Property(
        description: 'Numéro d\'allocataire CAF ou MSA.
                     <br>⚠️Pris en compte uniquement dans le cas où isAllocataire = true.',
        example: '1234567890123',
    )]
    #[Assert\Length(min: 1, max: 25)]
    public ?string $numAllocataire = null;

    #[OA\Property(
        description: 'Type de l\'allocation perçue par l\'allocataire.
                      <br>⚠️Pris en compte uniquement dans le cas où isAllocataire = true.',
        example: 'APL',
    )]
    #[Assert\Choice(
        choices: [
            'ALS',
            'AFL',
            'APL',
        ],
    )]
    public ?string $typeAllocation = null;

    #[OA\Property(
        description: 'Montant mensuel de l\'allocation perçue par l\'allocataire.
                      <br>⚠️Pris en compte uniquement dans le cas où isAllocataire = true.',
        example: 250.75,
    )]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Assert\LessThan(value: 10000)]
    public ?float $montantAllocation = null;

    #[OA\Property(
        description: 'L\'occupant est-il accompagné par un travailleur social ?',
        example: false,
    )]
    public ?bool $isAccompagnementTravailleurSocial = null;

    #[OA\Property(
        description: 'Nom de la structure du travailleur social accompagnant.
                     <br>⚠️Pris en compte uniquement dans le cas où isAccompagnementTravailleurSocial = true.',
        example: 'CCAS de Montpellier',
    )]
    #[Assert\Length(max: 255)]
    public ?string $accompagnementTravailleurSocialNomStructure = null;

    #[OA\Property(
        description: 'L\'occupant est-il bénéficiaire du RSA ?',
        example: false,
    )]
    public ?bool $isBeneficiaireRsa = null;

    #[OA\Property(
        description: 'L\'occupant est-il bénéficiaire du FSL ?',
        example: false,
    )]
    public ?bool $isBeneficiaireFsl = null;

    #[OA\Property(
        description: 'Le bailleur a-t-il été informé de la situation ?',
        example: true,
    )]
    public ?bool $isBailleurAverti = null;

    #[OA\Property(
        description: 'Date à laquelle le bailleur a été informé au format AAAA-MM-DD.
                      <br>⚠️Pris en compte uniquement dans le cas où isBailleurAverti = true.',
        example: '2023-02-15',
    )]
    #[Assert\Date(message: 'Veuillez saisir une date au format AAAA-MM-DD.')]
    public ?string $dateBailleurAverti = null;

    #[OA\Property(
        description: 'Moyen par lequel le bailleur a été informé.
                      <br>⚠️Pris en compte uniquement dans le cas où isBailleurAverti = true.',
        example: 'email',
    )]
    #[Assert\Choice(
        choices: [
            'courrier',
            'email',
            'telephone',
            'sms',
            'autre',
            'nsp',
        ],
    )]
    public ?string $moyenInformationBailleur = null;

    #[OA\Property(
        description: 'Réponse du bailleur.
                      <br>⚠️Pris en compte uniquement dans le cas où isBailleurAverti = true.',
        example: 'Le bailleur n\'a pas donné suite.',
    )]
    #[Assert\Length(max: 5000)]
    public ?string $reponseBailleur = null;

    #[OA\Property(
        description: 'Une demande de logement/relogement/mutation a-t-elle été faite ?',
        example: false,
    )]
    public ?bool $isDemandeRelogement = null;

    #[OA\Property(
        description: 'L\'occupant souhaite-t-il quitter le logement ?',
        example: false,
    )]
    public ?bool $isSouhaiteQuitterLogement = null;

    #[OA\Property(
        description: 'Un préavis de départ a-t-il été déposé ?',
        example: false,
    )]
    public ?bool $isPreavisDepartDepose = null;

    #[OA\Property(
        description: 'Le logement est-il assuré ?',
        example: false,
    )]
    public ?bool $isLogementAssure = null;

    #[OA\Property(
        description: 'L\'assurance a-t-elle été contactée ?
                     <br>⚠️Pris en compte uniquement dans le cas où isLogementAssure = true.',
        example: false,
    )]
    public ?bool $isAssuranceContactee = null;
    #[OA\Property(
        description: 'Réponse de l\'assurance.
                     <br>⚠️Pris en compte uniquement dans le cas où isLogementAssure = true et isAssuranceContactee = true.',
        example: 'L\'assurance refuse de couvrir les dégâts.',
    )]
    #[Assert\Length(max: 5000)]
    public ?string $reponseAssurance = null;

    #[OA\Property(
        description: 'Civilité de l\'occupant.',
        example: 'Mme',
    )]
    #[Assert\Choice(
        choices: [
            'M',
            'Mme',
        ],
    )]
    public ?string $civiliteOccupant = null;

    #[OA\Property(
        description: 'Nom de l\'occupant.
                     <br><strong>⚠️ Obligatoire en cas de profilDeclarant = "LOCATAIRE" ou "BAILLEUR_OCCUPANT".</strong>',
        example: 'Dupont',
    )]
    #[Assert\Length(max: 50)]
    public ?string $nomOccupant = null;

    #[OA\Property(
        description: 'Prénom de l\'occupant.
                     <br><strong>⚠️ Obligatoire en cas de profilDeclarant = "LOCATAIRE" ou "BAILLEUR_OCCUPANT".</strong>',
        example: 'Marie',
    )]
    #[Assert\Length(max: 50)]
    public ?string $prenomOccupant = null;

    #[OA\Property(
        description: 'E-mail de l\'occupant.
                     <br><strong>⚠️ Obligatoire en cas de profilDeclarant = "LOCATAIRE" ou "BAILLEUR_OCCUPANT".</strong>',
        example: 'marie.dupont@example.com',
    )]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail de l\'occupant n\'est pas valide.')]
    #[Assert\Length(max: 255)]
    public ?string $mailOccupant = null;

    #[OA\Property(
        description: 'Téléphone de l\'occupant.',
        example: '0639987654',
    )]
    #[AppAssert\TelephoneFormat]
    public ?string $telOccupant = null;

    #[OA\Property(
        description: 'Type de bailleur.',
        example: ProprioType::PARTICULIER->value,
    )]
    #[Assert\Choice(
        choices: [
            ProprioType::PARTICULIER->value,
            ProprioType::ORGANISME_SOCIETE->value,
        ],
    )]
    public ?string $typeBailleur = null;
    #[OA\Property(
        description: 'Dénomination du bailleur (nom de la société ou de l\'organisme).
                    <br>⚠️Pris en compte uniquement dans le cas où typeBailleur = "ORGANISME_SOCIETE".',
        example: 'Habitat & Co',
    )]
    #[Assert\Length(max: 255)]
    public ?string $denominationBailleur = null;

    #[OA\Property(
        description: 'Nom de famille du bailleur ou de son représentant.',
        example: 'Vignon',
    )]
    #[Assert\Length(max: 255)]
    public ?string $nomBailleur = null;

    #[OA\Property(
        description: 'Prénom du bailleur ou de son représentant.',
        example: 'René',
    )]
    #[Assert\Length(max: 255)]
    public ?string $prenomBailleur = null;

    #[OA\Property(
        description: 'E-mail du bailleur ou de son représentant.',
        example: 'rene.vignon@example.com',
    )]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail du bailleur n\'est pas valide.')]
    #[Assert\Length(max: 255)]
    public ?string $mailBailleur = null;

    #[OA\Property(
        description: 'Téléphone du bailleur ou de son représentant.',
        example: '0639980851',
    )]
    #[AppAssert\TelephoneFormat]
    public ?string $telBailleur = null;

    #[OA\Property(
        description: 'Adresse du bailleur ou de son représentant (numéro et voie).',
        example: '12 avenue des bartas',
    )]
    #[Assert\Length(max: 100)]
    public ?string $adresseBailleur = null;

    #[OA\Property(
        description: 'Code postal du bailleur ou de son représentant.',
        example: '34000',
    )]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
    public ?string $codePostalBailleur = null;

    #[OA\Property(
        description: 'Commune du bailleur ou de son représentant.',
        example: 'Montpellier',
    )]
    #[Assert\Length(max: 100)]
    public ?string $communeBailleur = null;

    #[OA\Property(
        description: 'Nom de la structure/organisme du déclarant.
                     <br>⚠️Pris en compte uniquement dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".',
        example: 'CAF 34',
    )]
    #[Assert\Length(max: 200)]
    public ?string $structureDeclarant = null;

    #[OA\Property(
        description: 'Nom de famille du déclarant.
                     <br>⚠️Pris en compte uniquement dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".
                     <br><strong>⚠️ Obligatoire dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".</strong>',
        example: 'El Allali',
    )]
    #[Assert\Length(max: 50)]
    public ?string $nomDeclarant = null;

    #[OA\Property(
        description: 'Prénom du déclarant.
                     <br>⚠️Pris en compte uniquement dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".
                     <br><strong>⚠️ Obligatoire dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".</strong>',
        example: 'Hakim',
    )]
    #[Assert\Length(max: 50)]
    public ?string $prenomDeclarant = null;
    #[OA\Property(
        description: 'E-mail du déclarant.
                     <br>⚠️Pris en compte uniquement dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".
                     <br><strong>⚠️ Obligatoire dans le cas où profilDeclarant = "SERVICE_SECOURS", "BAILLEUR", "TIERS_PRO" ou "TIERS_PARTICULIER".</strong>',
        example: 'el-allali.hakim@example.com',
    )]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail du déclarant n\'est pas valide.')]
    #[Assert\Length(max: 255)]
    public ?string $mailDeclarant = null;

    #[OA\Property(
        description: 'Téléphone du déclarant.',
        example: '0639980906',
    )]
    #[AppAssert\TelephoneFormat]
    public ?string $telDeclarant = null;

    #[OA\Property(
        description: 'Dénomination de l\'agence immobilière.',
        example: 'IMMO 3600',
    )]
    #[Assert\Length(max: 255)]
    public ?string $denominationAgence = null;

    #[OA\Property(
        description: 'Nom de famille du contact de l\'agence immobilière.',
        example: 'Apollo-Sanchez',
    )]
    #[Assert\Length(max: 255)]
    public ?string $nomAgence = null;

    #[OA\Property(
        description: 'Prénom du contact à l\'agence immobilière.',
        example: 'Victoria',
    )]
    #[Assert\Length(max: 255)]
    public ?string $prenomAgence = null;
    #[OA\Property(
        description: 'E-mail du contact à l\'agence immobilière.',
        example: 'victoria.apollo@immo3600.com',
    )]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail du contact à l\'agence immobilière n\'est pas valide.')]
    #[Assert\Length(max: 255)]
    public ?string $mailAgence = null;

    #[OA\Property(
        description: 'Téléphone du contact à l\'agence immobilière.',
        example: '0639988821',
    )]
    #[AppAssert\TelephoneFormat]
    public ?string $telAgence = null;

    #[OA\Property(
        description: 'Adresse de l\'agence immobilière (numéro et voie).',
        example: '185 Rue Léon Blum',
    )]
    #[Assert\Length(max: 100)]
    public ?string $adresseAgence = null;

    #[OA\Property(
        description: 'Code postal de l\'agence immobilière.',
        example: '34000',
    )]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
    public ?string $codePostalAgence = null;

    #[OA\Property(
        description: 'Commune de l\'agence immobilière.',
        example: 'Montpellier',
    )]
    #[Assert\Length(max: 100)]
    public ?string $communeAgence = null;

    /** @var array<DesordreRequest> $desordres */
    #[OA\Property(
        description: 'Liste des désordres constatés.
                     <br>Voir le <a href="/doc-api/desordres" target="_blank">tableau de référence des désordres</a>.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: DesordreRequest::class))
    )]
    #[Assert\NotBlank(message: 'Au moins un désordre doit être renseigné.')]
    #[Assert\Count(
        min: 1,
        minMessage: 'Au moins un désordre doit être renseigné.',
        max: 100,
        maxMessage: 'Vous ne pouvez pas renseigner plus de {{ limit }} désordres.'
    )]
    #[Assert\Valid]
    public array $desordres = [];

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        if ($this->profilDeclarant) {
            if (in_array($this->profilDeclarant, [ProfileDeclarant::LOCATAIRE->value, ProfileDeclarant::BAILLEUR_OCCUPANT->value])) {
                if (empty($this->nomOccupant)) {
                    $context->buildViolation('Veuillez renseigner le nom de l\'occupant.')->atPath('nomOccupant')->addViolation();
                }
                if (empty($this->prenomOccupant)) {
                    $context->buildViolation('Veuillez renseigner le prénom de l\'occupant.')->atPath('prenomOccupant')->addViolation();
                }
                if (empty($this->mailOccupant)) {
                    $context->buildViolation('Veuillez renseigner l\'e-mail de l\'occupant.')->atPath('mailOccupant')->addViolation();
                }
                if ($this->structureDeclarant) {
                    $context->buildViolation('Le champ structureDeclarant ne doit pas être renseigné si le profilDeclarant = LOCATAIRE ou BAILLEUR_OCCUPANT.')->atPath('structureDeclarant')->addViolation();
                }
                if ($this->nomDeclarant) {
                    $context->buildViolation('Le champ nomDeclarant ne doit pas être renseigné si le profilDeclarant = LOCATAIRE ou BAILLEUR_OCCUPANT.')->atPath('nomDeclarant')->addViolation();
                }
                if ($this->prenomDeclarant) {
                    $context->buildViolation('Le champ prenomDeclarant ne doit pas être renseigné si le profilDeclarant = LOCATAIRE ou BAILLEUR_OCCUPANT.')->atPath('prenomDeclarant')->addViolation();
                }
                if ($this->mailDeclarant) {
                    $context->buildViolation('Le champ mailDeclarant ne doit pas être renseigné si le profilDeclarant = LOCATAIRE ou BAILLEUR_OCCUPANT.')->atPath('mailDeclarant')->addViolation();
                }
            } else {
                if (empty($this->nomDeclarant)) {
                    $context->buildViolation('Veuillez renseigner le nom du déclarant.')->atPath('nomDeclarant')->addViolation();
                }
                if (empty($this->prenomDeclarant)) {
                    $context->buildViolation('Veuillez renseigner le prénom du déclarant.')->atPath('prenomDeclarant')->addViolation();
                }
                if (empty($this->mailDeclarant)) {
                    $context->buildViolation('Veuillez renseigner l\'e-mail du déclarant.')->atPath('mailDeclarant')->addViolation();
                }
            }
            if ($this->profilDeclarant !== ProfileDeclarant::TIERS_PARTICULIER->value && $this->lienDeclarantOccupant) {
                $context->buildViolation('Le champ lienDeclarantOccupant ne doit être renseigné que si le profilDeclarant = TIERS_PARTICULIER.')->atPath('lienDeclarantOccupant')->addViolation();
            }
            if (in_array($this->profilDeclarant, [ProfileDeclarant::TIERS_PARTICULIER->value, ProfileDeclarant::TIERS_PRO->value, ProfileDeclarant::SERVICE_SECOURS->value, ProfileDeclarant::BAILLEUR->value]) && $this->isLogementVacant) {
                $context->buildViolation('Le champ isLogementVacant ne peux être true que si le profilDeclarant = TIERS_PARTICULIER, TIERS_PRO, SERVICE_SECOURS ou BAILLEUR.')->atPath('isLogementVacant')->addViolation();
            }
        }
        if ($this->natureLogementAutre && 'autre' !== $this->natureLogement) {
            $context->buildViolation('Le champ natureLogementAutre ne peux être true que si natureLogement = "autre".')->atPath('natureLogementAutre')->addViolation();
        }
        if ($this->etageAppartement && 'appartement' !== $this->natureLogement) {
            $context->buildViolation('Le champ etageAppartement ne peux être true que si natureLogement = "appartement".')->atPath('etageAppartement')->addViolation();
        }
        if ($this->isAppartementAvecFenetres && 'appartement' !== $this->natureLogement) {
            $context->buildViolation('Le champ isAppartementAvecFenetres ne peux être true que si natureLogement = "appartement".')->atPath('isAppartementAvecFenetres')->addViolation();
        }
        if ($this->isCuisineCollective && false !== $this->isCuisine) {
            $context->buildViolation('Le champ isCuisineCollective ne peux être true que si isCuisine = false.')->atPath('isCuisineCollective')->addViolation();
        }
        if ($this->isSalleDeBainCollective && false !== $this->isSalleDeBain) {
            $context->buildViolation('Le champ isSalleDeBainCollective ne peux être true que si isSalleDeBain = false.')->atPath('isSalleDeBainCollective')->addViolation();
        }
        if ($this->isWcCollectif && false !== $this->isWc) {
            $context->buildViolation('Le champ isWcCollectif ne peux être true que si isWc = false.')->atPath('isWcCollectif')->addViolation();
        }
        if ($this->isWcCuisineMemePiece && (true !== $this->isWc || true !== $this->isCuisine)) {
            $context->buildViolation('Le champ isWcCuisineMemePiece ne peux être true que si isWc = true et isCuisine = true.')->atPath('isWcCuisineMemePiece')->addViolation();
        }
        if (true !== $this->isAllocataire) {
            if ($this->caisseAllocations) {
                $context->buildViolation('Le champ caisseAllocations ne doit être renseigné que si isAllocataire = true.')->atPath('caisseAllocations')->addViolation();
            }
            if ($this->dateNaissanceAllocataire) {
                $context->buildViolation('Le champ dateNaissanceAllocataire ne doit être renseigné que si isAllocataire = true.')->atPath('dateNaissanceAllocataire')->addViolation();
            }
            if ($this->numAllocataire) {
                $context->buildViolation('Le champ numAllocataire ne doit être renseigné que si isAllocataire = true.')->atPath('numAllocataire')->addViolation();
            }
            if ($this->typeAllocation) {
                $context->buildViolation('Le champ typeAllocation ne doit être renseigné que si isAllocataire = true.')->atPath('typeAllocation')->addViolation();
            }
            if ($this->montantAllocation) {
                $context->buildViolation('Le champ montantAllocation ne doit être renseigné que si isAllocataire = true.')->atPath('montantAllocation')->addViolation();
            }
        }
        if (true !== $this->isAccompagnementTravailleurSocial && $this->accompagnementTravailleurSocialNomStructure) {
            $context->buildViolation('Le champ accompagnementTravailleurSocialNomStructure ne doit être renseigné que si isAccompagnementTravailleurSocial = true.')->atPath('accompagnementTravailleurSocialNomStructure')->addViolation();
        }
        if (true !== $this->isBailleurAverti) {
            if ($this->dateBailleurAverti) {
                $context->buildViolation('Le champ dateBailleurAverti ne doit être renseigné que si isBailleurAverti = true.')->atPath('dateBailleurAverti')->addViolation();
            }
            if ($this->moyenInformationBailleur) {
                $context->buildViolation('Le champ moyenInformationBailleur ne doit être renseigné que si isBailleurAverti = true.')->atPath('moyenInformationBailleur')->addViolation();
            }
            if ($this->reponseBailleur) {
                $context->buildViolation('Le champ reponseBailleur ne doit être renseigné que si isBailleurAverti = true.')->atPath('reponseBailleur')->addViolation();
            }
        }
        if (true !== $this->isLogementAssure) {
            if ($this->isAssuranceContactee) {
                $context->buildViolation('Le champ isAssuranceContactee ne peut être true que si isLogementAssure = true.')->atPath('isAssuranceContactee')->addViolation();
            }
            if ($this->reponseAssurance) {
                $context->buildViolation('Le champ reponseAssurance ne doit être renseigné que si isLogementAssure = true.')->atPath('reponseAssurance')->addViolation();
            }
        }
        if ($this->typeBailleur !== ProprioType::ORGANISME_SOCIETE->value && $this->denominationBailleur) {
            $context->buildViolation('Le champ denominationBailleur ne doit être renseigné que si typeBailleur = "ORGANISME_SOCIETE".')->atPath('denominationBailleur')->addViolation();
        }
        if ($this->dateEntreeLogement) {
            $dateEntree = \DateTime::createFromFormat('Y-m-d', $this->dateEntreeLogement);
            $now = new \DateTime();
            if ($dateEntree && $dateEntree > $now) {
                $context->buildViolation('La date d\'entrée dans le logement ne peut pas être dans le futur.')->atPath('dateEntreeLogement')->addViolation();
            }
        }
    }
}
