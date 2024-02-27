<?php

namespace App\Factory\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Factory\Interconnection\DossierMessageFactoryInterface;
use App\Service\HtmlCleaner;
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

    protected function buildDesordresCreatedFrom(Signalement $signalement): string
    {
        if (!$signalement->getDesordreCriteres()->isEmpty()) {
            $criticitesArranged = [];
            foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
                $zone = $desordrePrecision->getDesordreCritere()->getZoneCategorie();
                $labelCategorieBO = $desordrePrecision->getDesordreCritere()->getDesordreCategorie()->getLabel();
                $labelCritere = $desordrePrecision->getDesordreCritere()->getLabelCritere();
                $criticitesArranged[$zone->value][$labelCategorieBO][$labelCritere][] = $desordrePrecision;
            }
        }
        if (empty($criticitesArranged)) {
            return '';
        }

        $commentaireDesordres = '';
        foreach ($criticitesArranged as $listZoneCategorie) {
            foreach ($listZoneCategorie as $labelCategorie => $listCritere) {
                foreach ($listCritere as $labelCritere => $listPrecision) {
                    $commentaireDesordres .= \PHP_EOL.$labelCritere;
                    if (\count($listPrecision) > 0) {
                        $commentairePrecision = '';
                        foreach ($listPrecision as $desordrePrecision) {
                            if ('' != $desordrePrecision->getLabel()) {
                                $commentairePrecision .= $desordrePrecision->getLabel().' ; ';
                            }
                        }
                        if (!empty($commentairePrecision)) {
                            $commentaireDesordres .= ' : '.$commentairePrecision;
                        }
                    }
                }
            }
        }

        return HtmlCleaner::clean($commentaireDesordres);
    }
}
