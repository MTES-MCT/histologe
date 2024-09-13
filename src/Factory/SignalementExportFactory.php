<?php

namespace App\Factory;

use App\Dto\SignalementExport;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\VisiteStatus;
use App\Entity\Intervention;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\User;
use App\Service\Signalement\SignalementAffectationHelper;
use App\Utils\DateHelper;

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

        $motifCloture = $data['motifCloture'] instanceof MotifCloture ? $data['motifCloture']->label() : null;
        $typeDeclarant = $data['profileDeclarant'] instanceof ProfileDeclarant ? $data['profileDeclarant']->label() : null;
        $status = SignalementAffectationHelper::getStatusLabelFrom($user, $data);

        $geoloc = $data['geoloc'];

        $lastIntervention = $this->getVisiteStatut($data['interventionsStatus']);
        $dateVisite = $lastIntervention['scheduledAt'];
        $dateVisite = (!empty($dateVisite) && DateHelper::isValidDate($dateVisite)) ? (new \DateTime($dateVisite))->format(self::DATE_FORMAT) : '';
        $isOccupantPresentVisite = $lastIntervention['occupantPresent'];

        $enfantsM6 = null;
        if (isset($data['typeCompositionLogement']) && $data['typeCompositionLogement'] instanceof TypeCompositionLogement) {
            $enfantsM6 = $data['typeCompositionLogement']->getCompositionLogementEnfants();
        } elseif (isset($data['nbEnfantsM6'])) {
            $enfantsM6 = ($data['nbEnfantsM6'] > 0) ? 'oui' : 'non';
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
            etiquettes: $data['etiquettes'] ?? null,
            photos: '-',
            documents: '-',
            isProprioAverti: $this->mapData($data, 'isProprioAverti'),
            nbPersonnes: $data['nbOccupantsLogement'],
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
            dateVisite: $dateVisite,
            isOccupantPresentVisite: $isOccupantPresentVisite ? self::OUI : ('0' === $isOccupantPresentVisite ? self::NON : ''),
            interventionStatus: $lastIntervention['status'],
            interventionConcludeProcedure: $data['interventionConcludeProcedure'] ?? '-',
            interventionDetails: !empty($data['interventionDetails']) ? strip_tags($data['interventionDetails']) : '-',
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
                $value = null === $data[$keyColumn]
                    ? self::NON_RENSEIGNE
                    : (\in_array($data[$keyColumn], self::ALLOCATAIRE) ? self::OUI : self::NON);
                break;
            default:
                break;
        }

        return $value;
    }

    private function getVisiteStatut(?string $interventionStatus): array
    {
        if (null === $interventionStatus) {
            return [
                'status' => VisiteStatus::NON_PLANIFIEE->value,
                'scheduledAt' => '',
                'occupantPresent' => '',
            ];
        }
        $interventionsExploded = explode(SignalementExport::SEPARATOR_GROUP_CONCAT, $interventionStatus);
        $lastIntervention = [
            'status' => $interventionsExploded[count($interventionsExploded) - 3],
            'scheduledAt' => $interventionsExploded[count($interventionsExploded) - 2],
            'occupantPresent' => $interventionsExploded[count($interventionsExploded) - 1],
        ];
        if (Intervention::STATUS_PLANNED === $lastIntervention['status']) {
            $todayDatetime = new \DateTime();
            if ($lastIntervention['scheduledAt'] > $todayDatetime->format('Y-m-d')) {
                $statusVisite = VisiteStatus::PLANIFIEE->value;
            } else {
                $statusVisite = VisiteStatus::CONCLUSION_A_RENSEIGNER->value;
            }
        } elseif (Intervention::STATUS_CANCELED === $lastIntervention['status']) {
            $statusVisite = 'Annulée';
        } elseif (Intervention::STATUS_NOT_DONE === $lastIntervention['status']) {
            $statusVisite = 'Non effectuée';
        } else {
            $statusVisite = VisiteStatus::TERMINEE->value;
        }

        $lastIntervention['status'] = $statusVisite;

        return $lastIntervention;
    }
}
