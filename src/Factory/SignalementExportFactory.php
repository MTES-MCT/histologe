<?php

namespace App\Factory;

use App\Dto\SignalementAffectationListView;
use App\Dto\SignalementExport;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\VisiteStatus;
use App\Entity\Intervention;
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

        $motifCloture = $data['motifCloture'] instanceof MotifCloture ? $data['motifCloture']->label() : null;
        $status = SignalementAffectationHelper::getStatusLabelFrom($user, $data);

        $geoloc = json_encode($data['geoloc']);

        $interventionExploded = explode(
            SignalementExport::SEPARATOR_GROUP_CONCAT,
            $this->getVisiteStatut($data['interventionsStatus'])
        );
        $statusVisite = $interventionExploded[0];
        $dateVisite = $interventionExploded[1] ?? '';
        $isOccupantPresentVisite = $interventionExploded[2] ?? '';

        return new SignalementExport(
            reference: $data['reference'],
            createdAt: $createdAt,
            statut: $status,
            score: $data['score'],
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
            situations: $data['listDesordreCategories'] ?? $data['oldSituations'] ?? null,
            desordres: $data['listDesordreCriteres'] ?? $data['oldCriteres'] ?? null,
            etiquettes: $data['etiquettes'] ?? null,
            photos: '-',
            documents: '-',
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
            lienDeclarantOccupant: $data['lienDeclarantOccupant'] ?? '-',
            dateVisite: $dateVisite,
            isOccupantPresentVisite: $isOccupantPresentVisite ? self::OUI : ('0' === $isOccupantPresentVisite ? self::NON : ''),
            interventionStatus: $statusVisite,
            interventionConcludeProcedure: $data['interventionConcludeProcedure'] ?? '-',
            interventionDetails: !empty($data['interventionDetails']) ? strip_tags($data['interventionDetails']) : '-',
            modifiedAt: $modifiedAt,
            closedAt: $closedAt,
            motifCloture: $motifCloture,
            geoloc: $geoloc,
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

    private function getVisiteStatut(?string $interventionStatus): string
    {
        if (null === $interventionStatus) {
            $statusVisite = VisiteStatus::NON_PLANIFIEE->value.SignalementExport::SEPARATOR_GROUP_CONCAT.''.SignalementExport::SEPARATOR_GROUP_CONCAT;
        } else {
            $interventions = explode(SignalementAffectationListView::SEPARATOR_CONCAT, $interventionStatus);
            foreach ($interventions as $intervention) {
                $interventionExploded = explode(SignalementExport::SEPARATOR_GROUP_CONCAT, $intervention);
                if (Intervention::STATUS_PLANNED === $interventionExploded[0]) {
                    $todayDatetime = new \DateTime();
                    if ($interventionExploded[1] > $todayDatetime->format('Y-m-d')) {
                        $statusVisite = VisiteStatus::PLANIFIEE->value;
                    } else {
                        $statusVisite = VisiteStatus::CONCLUSION_A_RENSEIGNER->value;
                    }
                } elseif (Intervention::STATUS_CANCELED === $interventionExploded[0]) {
                    $statusVisite = 'Annulée';
                } elseif (Intervention::STATUS_NOT_DONE === $interventionExploded[0]) {
                    $statusVisite = 'Non effectuée';
                } else {
                    $statusVisite = VisiteStatus::TERMINEE->value;
                }
                $statusVisite .= SignalementExport::SEPARATOR_GROUP_CONCAT.$interventionExploded[1] ?? '';
                $statusVisite .= SignalementExport::SEPARATOR_GROUP_CONCAT.$interventionExploded[2] ?? '';
            }
        }

        return $statusVisite;
    }
}
