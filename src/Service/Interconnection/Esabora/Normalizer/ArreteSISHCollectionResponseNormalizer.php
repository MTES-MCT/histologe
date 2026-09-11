<?php

namespace App\Service\Interconnection\Esabora\Normalizer;

use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;

class ArreteSISHCollectionResponseNormalizer
{
    public function normalize(
        DossierArreteSISHCollectionResponse $dossierArreteSISHCollectionResponse,
    ): DossierArreteSISHCollectionResponse {
        $normalizedCollection = [];

        foreach ($dossierArreteSISHCollectionResponse->getCollection() as $item) {
            $hasArrete = null !== $item->getDossNum();
            $hasArreteMainLevee = null !== $item->getArreteMLNumero();
            $hasArreteModificatif = null !== $item->getArreteModificatifNumero();

            // On ne fait rien si on n'a pas d'arrêté ou si on n'a ni modificatif ni mainlevée
            if (!$hasArrete || (!$hasArreteModificatif && !$hasArreteMainLevee)) {
                $normalizedCollection[] = $item;
                continue;
            }

            // État 1 : Arrêté initial (sans modificatif ni mainlevée)
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
                    null,
                    null,
                ],
            ]);

            // État 2 : Arrêté + modificatif (uniquement dans le cas complet avec modificatif ET mainlevée)
            if ($hasArreteModificatif && $hasArreteMainLevee) {
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
                        $item->getArreteModificatifDate(),
                        $item->getArreteModificatifNumero(),
                    ],
                ]);
            }

            // Dernier état (soit Arrêté + ML, soit Arrêté + Modif, soit Arrêté + Modif + ML)
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
