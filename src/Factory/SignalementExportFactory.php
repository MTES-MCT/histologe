<?php

namespace App\Factory;

use App\Dto\SignalementExport;
use App\Entity\Enum\MotifCloture;
use App\Entity\User;
use App\Service\Signalement\SignalementAffectationHelper;

class SignalementExportFactory
{
    public const OUI = 'Oui';
    public const NON = 'Non';
    public const NON_RENSEIGNE = 'Non renseigné';
    public const ALLOCATAIRE = ['CAF', 'MSA', 'Oui', 1];

    public function createInstanceFrom(User $user, array $data): SignalementExport
    {
        $createdAt = $data['createdAt'] instanceof \DateTimeImmutable ? $data['createdAt']->format('d/m/y') : null;
        $modifiedAt = $data['modifiedAt'] instanceof \DateTimeImmutable ? $data['modifiedAt']->format('d/m/y') : null;
        $closedAt = $data['closedAt'] instanceof \DateTimeImmutable ? $data['closedAt']->format('d/m/y') : null;
        $dateVisite = $data['dateVisite'] instanceof \DateTimeImmutable ? $data['dateVisite']->format('d/m/y') : null;

        $motifCloture = $data['motifCloture'] instanceof MotifCloture ? $data['motifCloture']->label() : null;
        $status = SignalementAffectationHelper::getStatusLabelFrom($user, $data);

        return new SignalementExport(
            reference: $data['reference'],
            createdAt: $createdAt,
            statut: $status,
            description: $data['details'],
            nomOccupant: $data['nomOccupant'],
            prenomOccupant: $data['prenomOccupant'],
            telephoneOccupant: $data['telOccupant'],
            telephoneOccupantBis: $data['telOccupantBis'] ?? self::NON_RENSEIGNE,
            emailOccupant: $data['mailOccupant'],
            adresseOccupant: $data['adresseOccupant'],
            cpOccupant: $data['cpOccupant'],
            villeOccupant: $data['villeOccupant'],
            inseeOccupant: $data['inseeOccupant'],
            etageOccupant: $data['etageOccupant'] ?? self::NON_RENSEIGNE,
            escalierOccupant: $data['escalierOccupant'] ?? self::NON_RENSEIGNE,
            numAppartOccupant: $data['numAppartOccupant'] ?? self::NON_RENSEIGNE,
            adresseAutreOccupant: $data['adresseAutreOccupant'] ?? self::NON_RENSEIGNE,
            situations: $data['familleSituation'] ?? null,
            desordres: $data['desordres'] ?? null,
            etiquettes: $data['etiquettes'] ?? null,
            photos: empty($data['photos']) ? self::NON : self::OUI,
            documents: empty($data['documents']) ? self::NON : self::OUI,
            isProprioAverti: null === $data['isProprioAverti'] ? self::NON_RENSEIGNE : (1 == $data['isProprioAverti'] ? self::OUI : self::NON),
            nbAdultes: $data['nbAdultes'],
            nbEnfantsM6: $data['nbEnfantsM6'] ?? self::NON_RENSEIGNE,
            nbEnfantsP6: $data['nbEnfantsP6'] ?? self::NON_RENSEIGNE,
            isAllocataire: null === $data['isAllocataire'] ? self::NON_RENSEIGNE : (\in_array($data['isAllocataire'], self::ALLOCATAIRE) ? self::OUI : self::NON),
            numAllocataire: $data['numAllocataire'] ?? self::NON_RENSEIGNE,
            natureLogement: $data['natureLogement'] ?? self::NON_RENSEIGNE,
            superficie: $data['superficie'] ?? self::NON_RENSEIGNE,
            nomProprio: $data['nomProprio'],
            isLogementSocial: null === $data['isLogementSocial'] ? self::NON_RENSEIGNE : (1 == $data['isLogementSocial'] ? self::OUI : self::NON),
            isPreavisDepart: null === $data['isPreavisDepart'] ? self::NON_RENSEIGNE : (1 == $data['isPreavisDepart'] ? self::OUI : self::NON),
            isRelogement: null === $data['isRelogement'] ? self::NON_RENSEIGNE : (1 == $data['isRelogement'] ? self::OUI : self::NON),
            isNotOccupant: 1 == $data['isNotOccupant'] ? self::OUI : self::NON,
            nomDeclarant: $data['nomDeclarant'] ?? '-',
            structureDeclarant: $data['structureDeclarant'] ?? '-',
            lienDeclarantOccupant: $data['lienDeclarantOccupant'],
            scoreCreation: $data['scoreCreation'],
            dateVisite: $dateVisite,
            isOccupantPresentVisite: 1 == $data['isOccupantPresentVisite'] ? self::OUI : self::NON,
            modifiedAt: $modifiedAt,
            closedAt: $closedAt,
            motifCloture: $motifCloture,
            scoreCloture: $data['scoreCloture']
        );
    }
}
