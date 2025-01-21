<?php

namespace App\Factory;

use App\Dto\SignalementExport;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MoyenContact;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\VisiteStatus;
use App\Entity\Intervention;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\User;
use App\Service\Signalement\SignalementAffectationHelper;
use App\Utils\DateHelper;

class SignalementExportFactory
{
    public const string OUI = 'Oui';
    public const string NON = 'Non';
    public const string NON_RENSEIGNE = 'Non renseigné';
    public const array ALLOCATAIRE = ['CAF', 'MSA', 'oui', 1];
    public const string DATE_FORMAT = 'd/m/Y';

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

        $motifCloture = $data['motifCloture'] instanceof MotifCloture ? $data['motifCloture']->label() : null;
        $typeDeclarant = $data['profileDeclarant'] instanceof ProfileDeclarant ? $data['profileDeclarant']->label() : null;
        $status = SignalementAffectationHelper::getStatusLabelFrom($user, $data);

        $geoloc = $data['geoloc'];
        $dateVisite = $data['interventionScheduledAt'];
        $dateVisite = (!empty($dateVisite) && DateHelper::isValidDate($dateVisite)) ? (new \DateTime($dateVisite))->format(self::DATE_FORMAT) : '';
        $interventionStatus = $this->mapInterventionStatus($dateVisite, $data['interventionStatus']);
        $isOccupantPresentVisite = $data['interventionOccupantPresent'];

        $nbEnfants = null;
        if (isset($data['typeCompositionLogement']) && $data['typeCompositionLogement'] instanceof TypeCompositionLogement) {
            $nbEnfants = $data['typeCompositionLogement']->getCompositionLogementNombreEnfants();
        } elseif (isset($data['nbEnfantsM6']) && isset($data['nbEnfantsP6'])) {
            $nbEnfantsM6 = (int) str_replace('+', '', $data['nbEnfantsM6'] ?? 0);
            $nbEnfantsP6 = (int) str_replace('+', '', $data['nbEnfantsP6'] ?? 0);
            $nbEnfants = $nbEnfantsM6 + $nbEnfantsP6;
        }

        $enfantsM6 = null;
        if (isset($data['typeCompositionLogement']) && $data['typeCompositionLogement'] instanceof TypeCompositionLogement) {
            $enfantsM6 = $data['typeCompositionLogement']->getCompositionLogementEnfants();
        } elseif (isset($data['nbEnfantsM6'])) {
            $enfantsM6 = ($data['nbEnfantsM6'] > 0) ? 'oui' : 'non';
        }

        $infoProcedureBailDate = null;
        if (isset($data['informationProcedure']) && $data['informationProcedure'] instanceof InformationProcedure) {
            $infoProcedureBailDate = $data['informationProcedure']->getInfoProcedureBailDate();
        }

        $infoProcedureBailMoyen = $infoProcedureBailMoyenLabel = null;
        if (isset($data['informationProcedure'])
                && $data['informationProcedure'] instanceof InformationProcedure
                && !empty($data['informationProcedure']->getInfoProcedureBailMoyen())) {
            $infoProcedureBailMoyen = strtoupper($data['informationProcedure']->getInfoProcedureBailMoyen());
            $infoProcedureBailMoyenLabel = MoyenContact::tryFrom($infoProcedureBailMoyen)?->label();
        }

        return new SignalementExport(
            reference: $data['reference'],
            createdAt: $createdAt,
            statut: $status,
            score: $data['score'],
            description: $data['details'],
            typeDeclarant: $typeDeclarant,
            nomOccupant: $data['nomOccupant'],
            prenomOccupant: $data['prenomOccupant'],
            telephoneOccupant: '\''.$data['telOccupant'].'\'',
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
            situations: $data['listDesordreCategories'] ?? $data['oldSituations'] ?? null,
            desordres: $data['listDesordreCriteres'] ?? $data['oldCriteres'] ?? null,
            debutDesordres: $this->mapData($data, 'debutDesordres'),
            etiquettes: $data['etiquettes'] ?? null,
            photos: '-',
            documents: '-',
            isProprioAverti: $this->mapData($data, 'isProprioAverti'),
            infoProcedureBailDate: $infoProcedureBailDate,
            infoProcedureBailMoyen: $infoProcedureBailMoyenLabel,
            nbPersonnes: $data['nbOccupantsLogement'],
            nbEnfants: $nbEnfants,
            enfantsM6: $enfantsM6,
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
            emailDeclarant: $data['mailDeclarant'] ?? '-',
            structureDeclarant: $data['structureDeclarant'] ?? '-',
            lienDeclarantOccupant: $data['lienDeclarantOccupant'] ?? '-',
            nbVisites: $data['interventionNbVisites'],
            dateVisite: $dateVisite,
            isOccupantPresentVisite: ($isOccupantPresentVisite && '-' !== $isOccupantPresentVisite) ? self::OUI : ('0' === $isOccupantPresentVisite ? self::NON : ''),
            interventionStatus: $interventionStatus,
            interventionConcludeProcedure: $data['interventionConcludeProcedure'],
            interventionDetails: $data['interventionDetails'],
            modifiedAt: $modifiedAt,
            closedAt: $closedAt,
            motifCloture: $motifCloture,
            longitude: is_array($geoloc) ? $geoloc['lng'] ?? '' : '',
            latitude: is_array($geoloc) ? $geoloc['lat'] ?? '' : '',
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
                $value = null === $data[$keyColumn] || '' === $data[$keyColumn]
                    ? self::NON_RENSEIGNE
                    : (\in_array($data[$keyColumn], self::ALLOCATAIRE) ? self::OUI : self::NON);
                break;
            case 'debutDesordres':
                $debutDesordres = $data[$keyColumn];
                if (null !== $debutDesordres) {
                    $value = $debutDesordres->label();
                } else {
                    $value = self::NON_RENSEIGNE;
                }
                break;
            default:
                break;
        }

        return $value;
    }

    private function mapInterventionStatus(?string $scheduledAt = null, ?string $status = null): string
    {
        if (empty($status)) {
            return VisiteStatus::NON_PLANIFIEE->value;
        }

        if (Intervention::STATUS_PLANNED === $status) {
            $todayDatetime = new \DateTime();
            if ($scheduledAt > $todayDatetime->format('Y-m-d')) {
                $statusVisite = VisiteStatus::PLANIFIEE->value;
            } else {
                $statusVisite = VisiteStatus::CONCLUSION_A_RENSEIGNER->value;
            }
        } elseif (Intervention::STATUS_CANCELED === $status) {
            $statusVisite = 'Annulée';
        } elseif (Intervention::STATUS_NOT_DONE === $status) {
            $statusVisite = 'Non effectuée';
        } else {
            $statusVisite = VisiteStatus::TERMINEE->value;
        }

        return $statusVisite;
    }
}
