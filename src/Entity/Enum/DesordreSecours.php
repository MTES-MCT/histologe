<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum DesordreSecours: string
{
    use EnumTrait;
    case LOGEMENT_INADAPTE = 'LOGEMENT_INADAPTE';
    case MAUVAIS_ETAT_BATI = 'MAUVAIS_ETAT_BATI';
    case HUMIDITE_MOISISSURES = 'HUMIDITE_MOISISSURES';
    case ABSENCE_CONFORT = 'ABSENCE_CONFORT';
    case CHAUFFAGE_DANGEREUX = 'CHAUFFAGE_DANGEREUX';
    case SECURITE_PERSONNES = 'SECURITE_PERSONNES';
    case RISQUE_ELECTRIQUE = 'RISQUE_ELECTRIQUE';
    case RISQUE_SATURNISME = 'RISQUE_SATURNISME';
    case INCURIE_SYNDROME_DIOGENE = 'INCURIE_SYNDROME_DIOGENE';
    case NUISIBLES = 'NUISIBLES';
    case PARTIES_COMMUNES_DEGRADEES = 'PARTIES_COMMUNES_DEGRADEES';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'LOGEMENT_INADAPTE' => 'Logement inadapté',
            'MAUVAIS_ETAT_BATI' => 'Mauvais état du bâti',
            'HUMIDITE_MOISISSURES' => 'Humidité généralisée, moisissures',
            'ABSENCE_CONFORT' => 'Absence de confort (chauffage, eau, etc.)',
            'CHAUFFAGE_DANGEREUX' => 'Chauffage dangereux',
            'SECURITE_PERSONNES' => 'Sécurité des personnes',
            'RISQUE_ELECTRIQUE' => 'Risque électrique',
            'RISQUE_SATURNISME' => 'Risque de saturnisme',
            'INCURIE_SYNDROME_DIOGENE' => 'Incurie & syndrome de Diogène',
            'NUISIBLES' => 'Nuisibles',
            'PARTIES_COMMUNES_DEGRADEES' => 'Parties communes dégradées',
        ];
    }

    /** @return array<string, string> */
    public static function getDescriptionList(): array
    {
        return [
            'LOGEMENT_INADAPTE' => 'Logement très exigu, bas de plafond (inférieur à 2,20m), sans ouvrant vers l\'extérieur avec lumière naturelle insuffisante, etc.',
            'MAUVAIS_ETAT_BATI' => 'Présence de fissures, plancher anormalement instable, risque de chute d’éléments (cheminée, tuiles, plafonds,escalier désolidarisé), etc.',
            'HUMIDITE_MOISISSURES' => 'Fuites ou infiltrations d’eau, tâches de moisissures, forte odeur d’humidité, etc.',
            'ABSENCE_CONFORT' => 'Chauffage absent ou insuffisant, ventilation non fonctionnelle, ouvrants en mauvais état, absence d’eau chaude, de cuisine avec évier, de WC, de salle d’eau, remontées d’odeur de canalisations, etc.',
            'CHAUFFAGE_DANGEREUX' => 'Logement calfeutré, sans ventilation, appareil ou conduit en mauvais état, toute situation menant à un risque d’intoxication au monoxyde de carbone.',
            'SECURITE_PERSONNES' => 'Escaliers dangereux, garde-corps (fenêtres, escaliers) instable ou absent, sol avec ressauts, etc.',
            'RISQUE_ELECTRIQUE' => 'Absence de compteur individuel, risques de contact direct avec des fils dénudés, traces d’échauffement, etc.',
            'RISQUE_SATURNISME' => 'Peintures dégradées, présence d’enfant ou de femme enceinte dans un immeuble ancien',
            'INCURIE_SYNDROME_DIOGENE' => 'Logement très sale, encombré de déchets, isolement social, refus d’accès au logement.',
            'NUISIBLES' => 'Rats, souris, punaises de lit, cafards, etc.',
            'PARTIES_COMMUNES_DEGRADEES' => '',
        ];
    }

    public function getDescription(): string
    {
        return self::getDescriptionList()[$this->name];
    }
}
