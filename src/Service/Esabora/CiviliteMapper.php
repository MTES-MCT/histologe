<?php

namespace App\Service\Esabora;

use App\Entity\Enum\ProprioType;
use App\Entity\Signalement;
use App\Service\Esabora\Enum\PersonneQualite;

class CiviliteMapper
{
    public static function mapOccupant(Signalement $signalement): ?PersonneQualite
    {
        if (!empty($signalement->getCiviliteOccupant())) {
            return 'mme' === $signalement->getCiviliteOccupant() ? PersonneQualite::MADAME : PersonneQualite::MONSIEUR;
        }


        return PersonneQualite::MADAME_MONSIEUR;
    }

    public static function mapProprio(Signalement $signalement): ?PersonneQualite
    {
        if (ProprioType::ORGANISME_SOCIETE === $signalement->getTypeProprio()) {
            return PersonneQualite::SOCIETE;
        }

        return null;
    }

    public static function mapDeclarant(Signalement $signalement): ?PersonneQualite
    {
        // For now, we don't know the declarant civilité
        return null;
    }
}
