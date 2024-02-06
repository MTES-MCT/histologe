<?php

namespace App\Factory\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Factory\Interconnection\DossierMessageFactoryInterface;
use App\Service\UploadHandlerService;

abstract class AbstractDossierMessageFactory implements DossierMessageFactoryInterface
{
    public function __construct(private readonly UploadHandlerService $uploadHandlerService)
    {
    }

    protected function isEsaboraPartnerActive(Affectation $affectation): bool
    {
        $partner = $affectation->getPartner();

        return $partner->canSyncWithEsabora();
    }

    protected function buildPiecesJointes(Signalement $signalement): array
    {
        $piecesJointes = [];
        foreach ($signalement->getFiles() as $file) {
            $filepath = $this->uploadHandlerService->getTmpFilepath($file->getFilename());
            if ($filepath) {
                $piecesJointes[] = [
                    'documentName' => substr($file->getTitle(), 0, 100),
                    'documentSize' => filesize($filepath),
                    'documentContent' => $file->getFilename(),
                ];
            }
        }

        return $piecesJointes;
    }
}
