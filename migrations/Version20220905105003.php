<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220905105003 extends AbstractMigration
{
    public const CRITERE_TO_ARCHIVED = [
        'Aspect des espaces extérieurs',
        'Nuisances de l\'environnement',
        'Murs extérieurs',
        'Charpentes',
        'Toitures',
        'Réseau eau potable',
        'Prévention des chutes',
        'Évacuation des eaux usées et raccordement',
        'Propreté',
        'Présence d\'animaux nuisibles ',
        'Éclairement naturel des pièces principales',
        'Organisation intérieure du logement',
        'Dimension des pièces / surface habitable',
        'Protection phonique / bruits extérieurs',
        'Protection phonique / bruits intérieurs',
        'Isolation thermique',
        '%Chaudière gaz:\r\nInstallation, sécurité%',
        'Évacuation des produits de combustion',
        'Toxiques, peintures au plomb',
        'Risque amiante',
        'Prévention des chutes de personnes',
        'Appréciation globale des manifestations d\'humidité',
        'Réseau d\'alimentation en eau potable',
        'Réseau d\'évacuation des eaux usées',
        'Réseau d\'électricité',
        'Réseau de gaz',
        'Moyens de chauffage',
        'Cuisine ou coin cuisine',
        'Toilettes',
        'Salle de bain ou salle d\'eau',
        'Entretien des lieux, propreté courante',
        'Sur-occupation',
    ];

    public function getDescription(): string
    {
        return 'Disable criteria that are obsolete';
    }

    public function up(Schema $schema): void
    {
        foreach (self::CRITERE_TO_ARCHIVED as $critere) {
            $this->addSql('UPDATE critere SET is_archive = 1, modified_at=NOW() WHERE label like "'.$critere.'"');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::CRITERE_TO_ARCHIVED as $critere) {
            $this->addSql('UPDATE critere SET is_archive = 0, modified_at=NOW() WHERE label like "'.$critere.'";');
        }
    }
}
