<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221223225911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update format signalement fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET naissance_occupant_at = null');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement ADD naissance_occupants VARCHAR(255) DEFAULT NULL, DROP naissance_occupant_at, CHANGE annee_construction annee_construction VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE type_energie_logement type_energie_logement VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE mail_declarant mail_declarant VARCHAR(255) DEFAULT NULL, CHANGE mail_occupant mail_occupant VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement ADD naissance_occupant_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP naissance_occupants, CHANGE annee_construction annee_construction INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE type_energie_logement type_energie_logement VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE mail_declarant mail_declarant VARCHAR(50) DEFAULT NULL, CHANGE mail_occupant mail_occupant VARCHAR(50) DEFAULT NULL');
    }
}
