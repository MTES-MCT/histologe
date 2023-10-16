<?php

namespace App\Factory\Esabora;

use App\Entity\Signalement;
use App\Service\UploadHandlerService;

abstract class AbstractDossierMessageFactory implements DossierMessageFactoryInterface
{
    public function __construct(private readonly UploadHandlerService $uploadHandlerService)
    {
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
