<?php

namespace App\Messenger\Message\Idoss;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Messenger\Message\DossierMessageInterface;
use App\Utils\AddressParser;

final class DossierMessage implements DossierMessageInterface
{
    private const string DEPT_BOUCHES_DU_RHONE = '13';
    private const string CODE_INSEE_BASSIN_VIE_MARSEILLE = '13055';
    private const int DESCRIPTION_MAX_LENGTH = 250;
    private int $signalementId;
    private int $partnerId;
    private ?PartnerType $partnerType;
    private ?string $action;
    private ?string $signalementUuid;
    private string $reference;
    private string $dateDepotSignalement;
    /**
     * @var array<string, ?string>
     */
    private array $declarant;
    /**
     * @var array<string, mixed>
     */
    private array $occupant;
    private ?string $adresse1;
    private ?string $adresse2;
    /**
     * @var array<string, ?string>
     */
    private array $proprietaire;
    private string $descriptionProblemes;
    private ?string $numAllocataire;
    private ?float $montantAllocation;
    private string $bailEnCour = 'ne sait pas';
    private ?string $dateEntreeLogement = null;
    private ?float $montantLoyer;
    private string $construitAv1949 = 'ne sait pas';
    private ?int $nbrPieceLogement;
    private ?int $nbrEtages;
    /**
     * @var array<string, mixed>
     */
    private array $etapes = [];

    public function __construct(Affectation $affectation)
    {
        $signalement = $affectation->getSignalement();
        $this->signalementId = $signalement->getId();
        $this->signalementUuid = $signalement->getUuid();
        $this->reference = $signalement->getReference();
        $this->partnerId = $affectation->getPartner()->getId();
        $this->dateDepotSignalement = $signalement->getCreatedAt()->format('m-d-Y');

        if (!$signalement->isTiersDeclarant()) {
            $this->declarant = [
                'nomDeclarant' => $signalement->getNomOccupant() ?? 'Non renseigné',
                'prenomDeclarant' => $signalement->getPrenomOccupant() ?? 'Non renseigné',
                'telephoneDeclarant' => $signalement->getTelOccupantDecoded(),
                'mailDeclarant' => $signalement->getMailOccupant(),
            ];
        } else {
            $this->declarant = [
                'nomDeclarant' => $signalement->getNomDeclarant() ?? 'Non renseigné',
                'prenomDeclarant' => $signalement->getPrenomDeclarant() ?? 'Non renseigné',
                'telephoneDeclarant' => $signalement->getTelDeclarantDecoded(),
                'mailDeclarant' => $signalement->getMailDeclarant(),
            ];
        }

        $addressParsed = AddressParser::parse($signalement->getAdresseOccupant());
        $this->occupant = [
            'nomOccupant' => $signalement->getNomOccupant() ?? 'Non renseigné',
            'prenomOccupant' => $signalement->getPrenomOccupant() ?? 'Non renseigné',
            'telephoneOccupant' => $signalement->getTelOccupantDecoded(),
            'mailOccupant' => $signalement->getMailOccupant(),
            'adresseLogement' => [
                'adresse' => $signalement->getAddressCompleteOccupant(),
                'novoie' => $addressParsed['number'],
                'nomvoie' => $addressParsed['street'],
                'CP' => $signalement->getCpOccupant(),
                'nomCommune' => $signalement->getVilleOccupant(),
                'codeInseeCommune' => $signalement->getInseeOccupant(),
            ],
        ];
        // seul code insee accepté par IDOSS pour le service Habitat de Marseille, on force la valeur tant qu'on est dans le 13
        if (str_starts_with($signalement->getInseeOccupant(), self::DEPT_BOUCHES_DU_RHONE)) {
            $this->occupant['adresseLogement']['codeInseeCommune'] = self::CODE_INSEE_BASSIN_VIE_MARSEILLE;
        }

        $this->adresse1 = $signalement->getComplementAdresseOccupant(false);
        $this->adresse2 = $signalement->getAdresseAutreOccupant();
        $this->proprietaire = [
            'nomProprietaire' => $signalement->getNomProprio(),
            'prenomProprietaire' => $signalement->getPrenomProprio(),
            'adresseProprietaire' => $signalement->getAdresseProprio(),
            'telephoneProprietaire' => $signalement->getTelProprioDecoded(),
            'mailProprietaire' => $signalement->getMailProprio(),
        ];
        $this->descriptionProblemes = !empty($signalement->getDetails()) ? mb_strimwidth($signalement->getDetails(), 0, self::DESCRIPTION_MAX_LENGTH) : '';
        $this->numAllocataire = $signalement->getNumAllocataire();
        $this->montantAllocation = $signalement->getMontantAllocation();
        if (true === $signalement->getIsBailEnCours()) {
            $this->bailEnCour = 'oui';
        } elseif (false === $signalement->getIsBailEnCours()) {
            $this->bailEnCour = 'non';
        }
        if ($signalement->getDateEntree()) {
            $this->dateEntreeLogement = $signalement->getDateEntree()->format('m-d-Y');
        }
        $this->montantLoyer = $signalement->getLoyer();
        if (true === $signalement->getIsConstructionAvant1949()) {
            $this->construitAv1949 = 'oui';
        } elseif (false === $signalement->getIsConstructionAvant1949()) {
            $this->construitAv1949 = 'non';
        }
        $this->nbrPieceLogement = $signalement->getNbPiecesLogement();
        $this->nbrEtages = (int) $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementNombreEtages();
        $this->etapes = [
            'nbrPersonne' => $signalement->getTypeCompositionLogement()?->getCompositionLogementNombrePersonnes(),
            'typeLogement' => $signalement->getNatureLogement(),
            'superficie' => $signalement->getSuperficie(),
            'dateConstruction' => $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementAnneeConstruction(),
        ];
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(?PartnerType $partnerType): self
    {
        $this->partnerType = $partnerType;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function getSignalementUuid(): ?string
    {
        return $this->signalementUuid;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getDateDepotSignalement(): string
    {
        return $this->dateDepotSignalement;
    }

    /**
     * @return array<string, ?string>
     */
    public function getDeclarant(): array
    {
        return $this->declarant;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOccupant(): array
    {
        return $this->occupant;
    }

    public function getAdresse1(): ?string
    {
        return $this->adresse1;
    }

    public function getAdresse2(): ?string
    {
        return $this->adresse2;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProprietaire(): array
    {
        return $this->proprietaire;
    }

    public function getDescriptionProblemes(): string
    {
        return $this->descriptionProblemes;
    }

    public function getNumAllocataire(): ?string
    {
        return $this->numAllocataire;
    }

    public function getMontantAllocation(): ?float
    {
        return $this->montantAllocation;
    }

    public function getBailEnCour(): string
    {
        return $this->bailEnCour;
    }

    public function getDateEntreeLogement(): ?string
    {
        return $this->dateEntreeLogement;
    }

    public function getMontantLoyer(): ?float
    {
        return $this->montantLoyer;
    }

    public function getConstruitAv1949(): string
    {
        return $this->construitAv1949;
    }

    public function getNbrPieceLogement(): ?int
    {
        return $this->nbrPieceLogement;
    }

    public function getNbrEtages(): ?int
    {
        return $this->nbrEtages;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEtape(): array
    {
        return $this->etapes;
    }
}
