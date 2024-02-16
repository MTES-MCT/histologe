<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DocumentType;

class SignalementDocumentTypeMapper
{
    public static function map(string $value): DocumentType
    {
        if (str_starts_with($value, 'desordres_logement_securite_plomb_details_diagnostique')) {
            return DocumentType::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE;
        }

        if (str_starts_with($value, 'desordres_')) {
            return DocumentType::SITUATION;
        }

        if (str_contains($value, 'dpe_bail')) {
            return DocumentType::SITUATION_FOYER_BAIL;
        }

        if (str_contains($value, 'dpe_dpe')) {
            return DocumentType::SITUATION_FOYER_DPE;
        }

        if (str_contains($value, 'dpe_etat_des_lieux')) {
            return DocumentType::SITUATION_FOYER_ETAT_DES_LIEUX;
        }

        return DocumentType::AUTRE;
    }
}
