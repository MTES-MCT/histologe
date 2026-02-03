<?php

namespace App\Service\Interconnection\Esabora\Normalizer;

use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;

class ArreteSISHCollectionResponseNormalizer
{
    public function normalize(DossierArreteSISHCollectionResponse $dossierArreteSISHCollectionResponse): DossierArreteSISHCollectionResponse
    {
        $normalizedCollection = [];

        foreach ($dossierArreteSISHCollectionResponse->getCollection() as $item) {
            $hasArrete = null !== $item->getArreteNumero();
            $hasArreteMainLevee = null !== $item->getArreteMLNumero();

            // On ne fait rien si on n’a pas à la fois un arrêté et une mainlevée
            if (!($hasArrete && $hasArreteMainLevee)) {
                $normalizedCollection[] = $item;
                continue;
            }

            // On supprime les infos de main-lévée
            $normalizedCollection[] = new DossierArreteSISH([
                'keyDataList' => [
                    null,
                    $item->getArreteId(),
                ],
                'columnDataList' => [
                    $item->getLogicielProvenance(),
                    $item->getReferenceDossier(),
                    $item->getDossNum(),
                    $item->getArreteDate(),
                    $item->getArreteNumero(),
                    $item->getArreteType(),
                    null,
                    null,
                ],
            ]);

            // On garde tout comme le fait Esabora lorsqu'une main-levée est envoyée
            $normalizedCollection[] = $item;
        }

        return DossierArreteSISHCollectionResponse::fromCollection(
            $normalizedCollection,
            $dossierArreteSISHCollectionResponse->getStatusCode(),
            $dossierArreteSISHCollectionResponse->getSasEtat(),
            $dossierArreteSISHCollectionResponse->getErrorReason()
        );
    }
}
