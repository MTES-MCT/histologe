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
    public const DATE_FORMAT = 'd/m/Y';

    public function createInstanceFrom(User $user, array $data): SignalementExport
    {
        $createdAt = $data['createdAt'] instanceof \DateTimeImmutable
            ? $data['createdAt']->format(self::DATE_FORMAT)
            : null;

        $modifiedAt = $data['modifiedAt'] instanceof \DateTimeImmutable
            ? $data['modifiedAt']->format(self::DATE_FORMAT)
            : null;

        $closedAt = $data['closedAt'] instanceof \DateTimeImmutable
            ? $data['closedAt']->format(self::DATE_FORMAT)
            : null;

        $dateVisite = $data['dateVisite'] instanceof \DateTimeImmutable
            ? $data['dateVisite']->format(self::DATE_FORMAT)
            : null;

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
            isProprioAverti: $this->mapData($data, 'isProprioAverti'),
            nbAdultes: $data['nbAdultes'],
            nbEnfantsM6: $data['nbEnfantsM6'] ?? self::NON_RENSEIGNE,
            nbEnfantsP6: $data['nbEnfantsP6'] ?? self::NON_RENSEIGNE,
            isAllocataire: $this->mapData($data, 'isAllocataire'),
            numAllocataire: $data['numAllocataire'] ?? self::NON_RENSEIGNE,
            natureLogement: $data['natureLogement'] ?? self::NON_RENSEIGNE,
            superficie: $data['superficie'] ?? self::NON_RENSEIGNE,
            nomProprio: $data['nomProprio'],
            isLogementSocial: $this->mapData($data, 'isLogementSocial'),
            isPreavisDepart: $this->mapData($data, 'isPreavisDepart'),
            isRelogement: $this->mapData($data, 'isRelogement'),
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

    private function mapData(array $data, string $keyColumn): ?string
    {
        $value = null;
        switch ($keyColumn) {
            case 'isProprioAverti':
            case 'isLogementSocial':
            case 'isPreavisDepart':
            case 'isRelogement':
                $value = null === $data[$keyColumn]
                    ? self::NON_RENSEIGNE
                    : (1 == $data[$keyColumn] ? self::OUI : self::NON);
                break;
            case 'isAllocataire':
                $value = null === $data[$keyColumn]
                    ? self::NON_RENSEIGNE
                    : (\in_array($data[$keyColumn], self::ALLOCATAIRE) ? self::OUI : self::NON);
                break;
            default:
                break;
        }

        return $value;
    }
}
