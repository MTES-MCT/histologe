<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217141902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs liés à l\'étape 1 du formulaire de service de secours';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_secours_route ADD slug VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD service_secours_id INT DEFAULT NULL, ADD matricule_declarant VARCHAR(255) DEFAULT NULL, ADD date_mission_service_secours DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', ADD origine_mission_service_secours VARCHAR(255) DEFAULT NULL, ADD ordre_mission_service_secours VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511465BC0C6B FOREIGN KEY (service_secours_id) REFERENCES service_secours_route (id)');
        $this->addSql('CREATE INDEX IDX_F4B5511465BC0C6B ON signalement (service_secours_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_secours_route DROP slug, DROP email, DROP phone');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511465BC0C6B');
        $this->addSql('DROP INDEX IDX_F4B5511465BC0C6B ON signalement');
        $this->addSql('ALTER TABLE signalement DROP service_secours_id, DROP matricule_declarant, DROP date_mission_service_secours, DROP origine_mission_service_secours, DROP ordre_mission_service_secours');
    }
}
