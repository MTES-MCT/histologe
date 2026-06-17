<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum TypeArrete: string
{
    use EnumTrait;
    // mise en sécurité
    case MISE_EN_SECURITE = 'Mise en sécurité';
    case MISE_EN_SECURITE_PROCEDURE_URGENTE = 'Mise en sécurité procédure urgente';
    case MISE_EN_SECURITE_MODIFICATIF = 'Mise en sécurité modificatif';
    // insalubrité
    case ARRETE_L_511_11_IMPROPRE = 'Arrêté L.511-11 - Impropre';
    case ARRETE_L_511_11_ORDINAIRE_IRREMEDIABLE = 'Arrêté L.511-11 - Ordinaire irrémédiable';
    case ARRETE_L_511_11_ORDINAIRE_REMEDIABLE = 'Arrêté L.511-11 - Ordinaire remédiable';
    case ARRETE_L_511_11_SUROCCUPATION = 'Arrêté L.511-11 - Suroccupation';
    case ARRETE_L_511_11_USAGE_NON_APPROPRIE = 'Arrêté L.511-11 - Usage non approprié';
    case ARRETE_L_511_19_INSALUBRITE = 'Arrêté L.511-19 - Insalubrité';
    case ARRETE_L_511_19_INSALUBRITE_SATURNISME = 'Arrêté L.511-19 - Insalubrité & Saturnisme';
    case ARRETE_L_511_19_SATURNISME = 'Arrêté L.511-19 - Saturnisme';
    case ARRETE_L_1331_26 = 'Arrêté L1331-26';
    // autres
    case ARRETE_L_1311_4 = 'Arrêté L.1311-4';
    case ARRETE_LETCHIMY_ARTICLE_10 = 'Arrêté Letchimy : article 10';

    /**
     * @return array<string>
     */
    public static function getMiseEnSecuriteChoices(): array
    {
        return [
            self::MISE_EN_SECURITE,
            self::MISE_EN_SECURITE_PROCEDURE_URGENTE,
            self::MISE_EN_SECURITE_MODIFICATIF,
        ];
    }

    /**
     * @return array<string>
     */
    public static function getInsalubriteChoices(): array
    {
        return [
            self::ARRETE_L_511_11_IMPROPRE,
            self::ARRETE_L_511_11_ORDINAIRE_IRREMEDIABLE,
            self::ARRETE_L_511_11_ORDINAIRE_REMEDIABLE,
            self::ARRETE_L_511_11_SUROCCUPATION,
            self::ARRETE_L_511_11_USAGE_NON_APPROPRIE,
            self::ARRETE_L_511_19_INSALUBRITE,
            self::ARRETE_L_511_19_INSALUBRITE_SATURNISME,
            self::ARRETE_L_511_19_SATURNISME,
            self::ARRETE_L_1331_26,
        ];
    }

    /**
     * @return array<string>
     */
    public static function getAutresChoices(): array
    {
        return [
            self::ARRETE_L_1311_4,
            self::ARRETE_LETCHIMY_ARTICLE_10,
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function getChoices(): array
    {
        $choices = [
            'Mise en sécurité' => self::getMiseEnSecuriteChoices(),
            'Insalubrité' => self::getInsalubriteChoices(),
            'Autres' => self::getAutresChoices(),
        ];

        return $choices;
    }
}
