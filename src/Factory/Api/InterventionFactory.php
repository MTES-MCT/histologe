<?php

namespace App\Factory\Api;

use App\Dto\Api\Model\Intervention as InterventionModel;
use App\Dto\Api\Model\Partner;
use App\Entity\Enum\DocumentType;
use App\Entity\Intervention;

readonly class InterventionFactory
{
    public function __construct(private FileFactory $fileFactory)
    {
    }

    public function createInstance(Intervention $intervention): InterventionModel
    {
        $interventionModel = new InterventionModel();
        $interventionModel->uuid = $intervention->getUuid();
        $interventionModel->dateIntervention = $intervention->getScheduledAt()->format(\DATE_ATOM);
        $interventionModel->type = $intervention->getType();
        $interventionModel->statut = $intervention->getStatus();
        $interventionModel->partner = $intervention->getPartner() ? new Partner($intervention->getPartner()) : null;
        $interventionModel->details = $intervention->getDetails();
        $interventionModel->conclusions = $intervention->getConcludeProcedure() ?? [];
        $interventionModel->occupantPresent = $intervention->isOccupantPresent();
        $interventionModel->proprietairePresent = $intervention->isProprietairePresent();
        $signalement = $intervention->getSignalement();

        foreach ($signalement->getFiles() as $file) {
            if (in_array(
                $file->getDocumentType(),
                [DocumentType::PHOTO_VISITE, DocumentType::PROCEDURE_RAPPORT_DE_VISITE])
            ) {
                $interventionModel->files[] = $this->fileFactory->createFrom($file);
            }
        }

        return $interventionModel;
    }
}
