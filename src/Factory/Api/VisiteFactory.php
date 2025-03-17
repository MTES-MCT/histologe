<?php

namespace App\Factory\Api;

use App\Dto\Api\Model\Partner;
use App\Dto\Api\Model\Visite;
use App\Entity\Enum\DocumentType;
use App\Entity\Intervention;

readonly class VisiteFactory
{
    public function __construct(private FileFactory $fileFactory)
    {
    }

    public function createInstance(Intervention $intervention): Visite
    {
        $visite = new Visite();
        $visite->uuid = $intervention->getUuid();
        $visite->dateIntervention = $intervention->getScheduledAt()->format(\DATE_ATOM);
        $visite->type = $intervention->getType();
        $visite->statut = $intervention->getStatus();
        $visite->partner = $intervention->getPartner() ? new Partner($intervention->getPartner()) : null;
        $visite->details = $intervention->getDetails();
        $visite->conclusions = $intervention->getConcludeProcedure() ?? [];
        $visite->occupantPresent = $intervention->isOccupantPresent();
        $visite->proprietairePresent = $intervention->isProprietairePresent();
        $signalement = $intervention->getSignalement();

        foreach ($signalement->getFiles() as $file) {
            if (in_array(
                $file->getDocumentType(),
                [DocumentType::PHOTO_VISITE, DocumentType::PROCEDURE_RAPPORT_DE_VISITE])
                && $file->getIntervention()->getId() == $intervention->getId()
            ) {
                $visite->files[] = $this->fileFactory->createFrom($file);
            }
        }

        return $visite;
    }
}
