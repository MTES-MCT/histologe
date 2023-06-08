<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230608131408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention ADD additional_information JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE job_event CHANGE service service VARCHAR(255) NOT NULL, CHANGE action action VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention DROP additional_information');
        $this->addSql('ALTER TABLE job_event CHANGE service service VARCHAR(255) DEFAULT NULL, CHANGE action action VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT 0');
    }
}
