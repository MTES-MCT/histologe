<?php

namespace App\Messenger\Message\Idoss;

use App\Entity\Affectation;
use App\Messenger\Message\DossierMessageInterface;
use App\Utils\AddressParser;

final class DossierMessage implements DossierMessageInterface
{
    private const DEPT_BOUCHES_DU_RHONE = '13';
    private const CODE_INSEE_BASSIN_VIE_MARSEILLE = '13055';
    private const DESCRIPTION_MAX_LENGTH = 250;
    private int $signalementId;
    private int $partnerId;
    private ?string $signalementUuid;
    private string $dateDepotSignalement;
    private array $declarant;
    private array $occupant;
    private ?string $adresse1;
    private ?string $adresse2;
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
    private array $etapes = [];

    public function __construct(Affectation $affectation)
    {
        $this->signalementId = $affectation->getSignalement()->getId();
        $this->signalementUuid = $affectation->getSignalement()->getUuid();
        $this->partnerId = $affectation->getPartner()->getId();
        $this->dateDepotSignalement = $affectation->getSignalement()->getCreatedAt()->format('m-d-Y');

        $this->declarant = [
            'nomDeclarant' => $affectation->getSignalement()->getNomDeclarant(),
            'prenomDeclarant' => $affectation->getSignalement()->getPrenomDeclarant(),
            'telephoneDeclarant' => $affectation->getSignalement()->getTelDeclarantDecoded(),
            'mailDeclarant' => $affectation->getSignalement()->getMailDeclarant(),
        ];
        $addressParsed = AddressParser::parse($affectation->getSignalement()->getAdresseOccupant());
        $this->occupant = [
            'nomOccupant' => $affectation->getSignalement()->getNomOccupant(),
            'prenomOccupant' => $affectation->getSignalement()->getPrenomOccupant(),
            'telephoneOccupant' => $affectation->getSignalement()->getTelOccupantDecoded(),
            'mailOccupant' => $affectation->getSignalement()->getMailOccupant(),
            'adresseLogement' => [
                'adresse' => $affectation->getSignalement()->getAddressCompleteOccupant(),
                'novoie' => $addressParsed['number'],
                'nomvoie' => $addressParsed['street'],
                'CP' => $affectation->getSignalement()->getCpOccupant(),
                'nomCommune' => $affectation->getSignalement()->getVilleOccupant(),
                'codeInseeCommune' => $affectation->getSignalement()->getInseeOccupant(),
            ],
        ];
        // seul code insee accepté par IDOSS pour le service Habitat de Marseille, on force la valeur tant qu'on est dans le 13
        if (str_starts_with($affectation->getSignalement()->getInseeOccupant(), self::DEPT_BOUCHES_DU_RHONE)) {
            $this->occupant['adresseLogement']['codeInseeCommune'] = self::CODE_INSEE_BASSIN_VIE_MARSEILLE;
        }

        $this->adresse1 = $affectation->getSignalement()->getComplementAdresseOccupant(false);
        $this->adresse2 = $affectation->getSignalement()->getAdresseAutreOccupant();
        $this->proprietaire = [
            'nomProprietaire' => $affectation->getSignalement()->getNomProprio(),
            'prenomProprietaire' => $affectation->getSignalement()->getPrenomProprio(),
            'adresseProprietaire' => $affectation->getSignalement()->getAdresseProprio(),
            'telephoneProprietaire' => $affectation->getSignalement()->getTelProprioDecoded(),
            'mailProprietaire' => $affectation->getSignalement()->getMailProprio(),
        ];
        $this->descriptionProblemes = mb_strimwidth($affectation->getSignalement()->getDetails(), 0, self::DESCRIPTION_MAX_LENGTH);
        $this->numAllocataire = $affectation->getSignalement()->getNumAllocataire();
        $this->montantAllocation = $affectation->getSignalement()->getMontantAllocation();
        if (true === $affectation->getSignalement()->getIsBailEnCours()) {
            $this->bailEnCour = 'oui';
        } elseif (false === $affectation->getSignalement()->getIsBailEnCours()) {
            $this->bailEnCour = 'non';
        }
        if ($affectation->getSignalement()->getDateEntree()) {
            $this->dateEntreeLogement = $affectation->getSignalement()->getDateEntree()->format('m-d-Y');
        }
        $this->montantLoyer = $affectation->getSignalement()->getLoyer();
        if (true === $affectation->getSignalement()->getIsConstructionAvant1949()) {
            $this->construitAv1949 = 'oui';
        } elseif (false === $affectation->getSignalement()->getIsConstructionAvant1949()) {
            $this->construitAv1949 = 'non';
        }
        $this->nbrPieceLogement = $affectation->getSignalement()->getNbPiecesLogement();
        $this->nbrEtages = (int) $affectation->getSignalement()->getInformationComplementaire()?->getInformationsComplementairesLogementNombreEtages();
        $this->etapes = [
            'nbrPersonne' => $affectation->getSignalement()->getTypeCompositionLogement()?->getCompositionLogementNombrePersonnes(),
            'typeLogement' => $affectation->getSignalement()->getNatureLogement(),
            'superficie' => $affectation->getSignalement()->getSuperficie(),
            'dateConstruction' => $affectation->getSignalement()->getInformationComplementaire()?->getInformationsComplementairesLogementAnneeConstruction(),
        ];
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function getSignalementUuid(): ?string
    {
        return $this->signalementUuid;
    }

    public function getDateDepotSignalement(): string
    {
        return $this->dateDepotSignalement;
    }

    public function getDeclarant(): array
    {
        return $this->declarant;
    }

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

    public function getEtape(): array
    {
        return $this->etapes;
    }
}
