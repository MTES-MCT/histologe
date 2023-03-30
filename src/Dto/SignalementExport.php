<?php

namespace App\Dto;

class SignalementExport
{
    public const SEPARATOR_GROUP_CONCAT = '|';

    public function __construct(
        public ?string $reference = null,
        public ?string $createdAt = null,
        public ?string $statut = null,
        public ?string $description = null,
        public ?string $nomOccupant = null,
        public ?string $prenomOccupant = null,
        public ?string $telephoneOccupant = null,
        public ?string $telephoneOccupantBis = null,
        public ?string $emailOccupant = null,
        public ?string $adresseOccupant = null,
        public ?string $cpOccupant = null,
        public ?string $villeOccupant = null,
        public ?string $inseeOccupant = null,
        public ?string $etageOccupant = null,
        public ?string $escalierOccupant = null,
        public ?string $numAppartOccupant = null,
        public ?string $adresseAutreOccupant = null,
        public ?string $situations = null,
        public ?string $desordres = null,
        public ?string $etiquettes = null,
        public ?string $photos = null,
        public ?string $documents = null,
        public ?string $isProprioAverti = null,
        public ?string $nbAdultes = null,
        public ?string $nbEnfantsM6 = null,
        public ?string $nbEnfantsP6 = null,
        public ?string $isAllocataire = null,
        public ?string $numAllocataire = null,
        public ?string $natureLogement = null,
        public ?string $superficie = null,
        public ?string $nomProprio = null,
        public ?string $isLogementSocial = null,
        public ?string $isPreavisDepart = null,
        public ?string $isRelogement = null,
        public ?string $isNotOccupant = null,
        public ?string $nomDeclarant = null,
        public ?string $structureDeclarant = null,
        public ?string $lienDeclarantOccupant = null,
        public ?string $isSituationHandicap = null,
        public ?string $scoreCreation = null,
        public ?string $dateVisite = null,
        public ?string $isOccupantPresentVisite = null,
        public ?string $modifiedAt = null,
        public ?string $closedAt = null,
        public ?string $motifCloture = null,
        public ?string $scoreCloture = null,
    ) {
    }
}
