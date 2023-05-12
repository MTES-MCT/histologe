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
        foreach ($signalement->getDocuments() as $document) {
            $filepath = $this->uploadHandlerService->getTmpFilepath($document['file']);
            $piecesJointes[] = [
                'documentName' => $document['titre'],
                'documentSize' => filesize($filepath),
                'documentContent' => $document['file'],
            ];
        }
        foreach ($signalement->getPhotos() as $photo) {
            $filepath = $this->uploadHandlerService->getTmpFilepath($photo['file']);
            $piecesJointes[] = [
                'documentName' => 'Image téléversée',
                'documentSize' => filesize($filepath),
                'documentContent' => $photo['file'],
            ];
        }

        return $piecesJointes;
    }
}
