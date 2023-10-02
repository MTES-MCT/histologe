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
            $piecesJointes[] = [
                'documentName' => $file->getTitle(), // TODO : 100 caractères max
                'documentSize' => filesize($filepath),
                'documentContent' => $file->getFilename(), // TODO : c'est ce qui est attendu ? "Contenu du document encodé en base 64 " dans la doc
            ];
        }

        return $piecesJointes;
    }
}
