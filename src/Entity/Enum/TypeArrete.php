<?php

namespace App\Entity\Enum;

enum TypeArrete: string
{
    // mise en sécurité
    case MISE_EN_SECURITE = 'mise en sécurité';
    case MISE_EN_SECURITE_PROCEDURE_URGENTE = 'mise en sécurité procédure urgente';
    case MISE_EN_SECURITE_MODIFICATIF = 'mise en sécurité modificatif';
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
}
