<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum TypeArrete: string
{
    use EnumTrait;
    // mise en sécurité
    case MISE_EN_SECURITE = 'MISE_EN_SECURITE';
    case MISE_EN_SECURITE_PROCEDURE_URGENTE = 'MISE_EN_SECURITE_PROCEDURE_URGENTE';
    case MISE_EN_SECURITE_MODIFICATIF = 'MISE_EN_SECURITE_MODIFICATIF';
    // insalubrité
    case ARRETE_L_511_11_IMPROPRE = 'ARRETE_L_511_11_IMPROPRE';
    case ARRETE_L_511_11_ORDINAIRE_IRREMEDIABLE = 'ARRETE_L_511_11_ORDINAIRE_IRREMEDIABLE';
    case ARRETE_L_511_11_ORDINAIRE_REMEDIABLE = 'ARRETE_L_511_11_ORDINAIRE_REMEDIABLE';
    case ARRETE_L_511_11_SUROCCUPATION = 'ARRETE_L_511_11_SUROCCUPATION';
    case ARRETE_L_511_11_USAGE_NON_APPROPRIE = 'ARRETE_L_511_11_USAGE_NON_APPROPRIE';
    case ARRETE_L_511_19_INSALUBRITE = 'ARRETE_L_511_19_INSALUBRITE';
    case ARRETE_L_511_19_INSALUBRITE_SATURNISME = 'ARRETE_L_511_19_INSALUBRITE_SATURNISME';
    case ARRETE_L_511_19_SATURNISME = 'ARRETE_L_511_19_SATURNISME';
    case ARRETE_L_1331_26 = 'ARRETE_L_1331_26';
    // autres
    case ARRETE_L_1311_4 = 'ARRETE_L_1311_4';
    case ARRETE_LETCHIMY_ARTICLE_10 = 'ARRETE_LETCHIMY_ARTICLE_10';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::MISE_EN_SECURITE->name => 'Mise en sécurité',
            self::MISE_EN_SECURITE_PROCEDURE_URGENTE->name => 'Mise en sécurité procédure urgente',
            self::MISE_EN_SECURITE_MODIFICATIF->name => 'Mise en sécurité modificatif',
            self::ARRETE_L_511_11_IMPROPRE->name => 'Arrêté L.511-11 - Impropre',
            self::ARRETE_L_511_11_ORDINAIRE_IRREMEDIABLE->name => 'Arrêté L.511-11 - Ordinaire irrémédiable',
            self::ARRETE_L_511_11_ORDINAIRE_REMEDIABLE->name => 'Arrêté L.511-11 - Ordinaire remédiable',
            self::ARRETE_L_511_11_SUROCCUPATION->name => 'Arrêté L.511-11 - Suroccupation',
            self::ARRETE_L_511_11_USAGE_NON_APPROPRIE->name => 'Arrêté L.511-11 - Usage non approprié',
            self::ARRETE_L_511_19_INSALUBRITE->name => 'Arrêté L.511-19 - Insalubrité',
            self::ARRETE_L_511_19_INSALUBRITE_SATURNISME->name => 'Arrêté L.511-19 - Insalubrité & Saturnisme',
            self::ARRETE_L_511_19_SATURNISME->name => 'Arrêté L.511-19 - Saturnisme',
            self::ARRETE_L_1331_26->name => 'Arrêté L1331-26',
            self::ARRETE_L_1311_4->name => 'Arrêté L.1311-4',
            self::ARRETE_LETCHIMY_ARTICLE_10->name => 'Arrêté Letchimy : article 10',
        ];
    }

    /**
     * @return array<self>
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
     * @return array<self>
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
     * @return array<self>
     */
    public static function getAutresChoices(): array
    {
        return [
            self::ARRETE_L_1311_4,
            self::ARRETE_LETCHIMY_ARTICLE_10,
        ];
    }

    /**
     * @return array<string, array<self>>
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
