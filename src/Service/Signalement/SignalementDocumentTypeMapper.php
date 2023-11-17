<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DocumentType;

class SignalementDocumentTypeMapper
{
    public static function map(string $value): DocumentType
    {
        if (str_starts_with($value, 'desordres_')) {
            return DocumentType::SITUATION;
        }

        if (str_contains($value, 'dpe_bail')) {
            return DocumentType::SITUATION_FOYER_BAIL;
        }

        if (str_contains($value, 'dpe_dpe')) {
            return DocumentType::SITUATION_FOYER_DPE;
        }

        return DocumentType::AUTRE;
    }
}
