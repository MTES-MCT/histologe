<?php

namespace App\Service\Intervention;

use App\Dto\Api\Request\ArreteRequest;
use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;

class InterventionDescriptionGenerator
{
    public static function generate(Intervention $intervention, string $eventName): ?string
    {
        if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
            return $intervention->getDetails();
        }
        if (InterventionCreatedEvent::NAME === $eventName) {
            return self::buildDescriptionVisiteCreated($intervention);
        } elseif (InterventionUpdatedByEsaboraEvent::NAME === $eventName) {
            return self::buildDescriptionVisiteUpdated($intervention);
        }

        return null;
    }

    public static function buildDescriptionVisiteCreated(Intervention $intervention): string
    {
        $labelVisite = strtolower($intervention->getType()->label());
        $partnerName = $intervention->getExternalOperator() ?? $intervention->getPartner()?->getNom() ?? 'Non renseigné';
        $today = new \DateTimeImmutable();
        $isInPast = $today > $intervention->getScheduledAt()
            && Intervention::STATUS_DONE === $intervention->getStatus();

        return \sprintf(
            '%s %s : une %s du logement situé %s %s le %s.<br>La %s %s par %s.',
            ucfirst($labelVisite),
            $isInPast ? 'réalisée' : 'programmée',
            $labelVisite,
            $intervention->getSignalement()->getAdresseOccupant(),
            $isInPast ? 'a été effectuée' : 'est prévue',
            $intervention->getScheduledAt()->format('d/m/Y'),
            $labelVisite,
            $isInPast ? 'a été réalisée' : 'sera effectuée',
            $partnerName
        );
    }

    public static function buildDescriptionVisiteUpdated(Intervention $intervention): string
    {
        $labelVisite = strtolower($intervention->getType()->label());

        // Pour l'instant le seul besoin remonté par SISH est celui de la modification de date
        // Mais l'opérateur pourrait aussi être modifié (que ce soit ARS ou un opérateur externe)
        return \sprintf(
            'La date de %s dans %s a été modifiée ; La nouvelle date est le %s.',
            $labelVisite,
            EsaboraSISHService::NAME_SI,
            $intervention->getScheduledAt()->format('d/m/Y'),
        );
    }

    public static function buildDescriptionArreteCreated(DossierArreteSISH $dossierArreteSISH): string
    {
        $description = \sprintf(
            'L\'arrêté %s du %s a été pris dans le dossier de n°%s.<br>',
            $dossierArreteSISH->getArreteNumero(),
            $dossierArreteSISH->getArreteDate(),
            $dossierArreteSISH->getDossNum(),
        );

        $description .= \sprintf('Type arrêté: %s<br>', $dossierArreteSISH->getArreteType());

        if ($dossierArreteSISH->getArreteMLDate()) {
            $description = \sprintf(
                'Un arrêté de mainlevée %s du %s a été pris pour l\'arrêté %s du %s dans le dossier de n°%s.',
                $dossierArreteSISH->getArreteMLNumero(),
                $dossierArreteSISH->getArreteMLDate(),
                $dossierArreteSISH->getArreteNumero(),
                $dossierArreteSISH->getArreteDate(),
                $dossierArreteSISH->getDossNum()
            );
        }

        return $description;
    }

    public static function buildDescriptionArreteCreatedFromRequest(ArreteRequest $arreteRequest): string
    {
        $description = \sprintf(
            'L\'arrêté %s du %s a été pris dans le dossier n°%s.<br>',
            $arreteRequest->numero,
            $arreteRequest->date,
            $arreteRequest->numeroDossier,
        );

        $description .= \sprintf('Type arrêté : %s<br>', $arreteRequest->type);

        if ($arreteRequest->mainLeveeDate) {
            $description = \sprintf(
                'Un arrêté de mainlevée%sdu %s a été pris pour l\'arrêté %s du %s dans le dossier n°%s.',
                $arreteRequest->mainLeveeNumero ? ' '.$arreteRequest->mainLeveeNumero.' ' : ' ',
                $arreteRequest->mainLeveeDate,
                $arreteRequest->numero,
                $arreteRequest->date,
                $arreteRequest->numeroDossier
            );
        }

        return $description;
    }
}
