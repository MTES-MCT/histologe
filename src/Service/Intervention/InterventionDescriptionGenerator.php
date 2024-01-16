<?php

namespace App\Service\Intervention;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Event\InterventionCreatedEvent;
use App\Service\Esabora\Response\Model\DossierArreteSISH;

class InterventionDescriptionGenerator
{
    public static function generate(Intervention $intervention, string $eventName): ?string
    {
        if (InterventionCreatedEvent::NAME === $eventName) {
            if (InterventionType::ARRETE_PREFECTORAL === $intervention->getType()) {
                return $intervention->getDetails();
            }

            return self::buildDescriptionVisiteCreated($intervention);
        }

        return null;
    }

    public static function buildDescriptionVisiteCreated(Intervention $intervention): string
    {
        $labelVisite = strtolower($intervention->getType()->label());
        $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
        $today = new \DateTimeImmutable();
        $isInPast = $today > $intervention->getScheduledAt()
            && Intervention::STATUS_DONE === $intervention->getStatus();

        return sprintf(
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

    public static function buildDescriptionArreteCreated(DossierArreteSISH $dossierArreteSISH): string
    {
        $description = sprintf(
            'L\'arrêté %s du %s dans le dossier de n°%s.<br>',
            $dossierArreteSISH->getArreteNumero(),
            $dossierArreteSISH->getArreteDate(),
            $dossierArreteSISH->getDossNum(),
        );

        $description .= sprintf('Type arrêté: %s<br>', $dossierArreteSISH->getArreteType());

        if ($dossierArreteSISH->getArreteMLDate()) {
            $description .= sprintf(
                'Pour cet arrêté, il a également été pris un arrêté de mainlevée %s du %s.',
                $dossierArreteSISH->getArreteMLNumero(),
                $dossierArreteSISH->getArreteMLDate()
            );
        }

        return $description;
    }
}
