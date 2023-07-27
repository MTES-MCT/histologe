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

        return sprintf(
            '%s programmée : une %s du logement situé %s est prévue le %s.<br>La %s sera effectuée par %s.',
            ucfirst($labelVisite),
            $labelVisite,
            $intervention->getSignalement()->getAdresseOccupant(),
            $intervention->getScheduledAt()->format('d/m/Y'),
            $labelVisite,
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
