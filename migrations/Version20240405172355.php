<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240405172355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add epci table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE epci (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, UNIQUE INDEX code_unique (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commune ADD epci_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commune ADD CONSTRAINT FK_E2E2D1EE4E7C18CB FOREIGN KEY (epci_id) REFERENCES epci (id)');
        $this->addSql('CREATE INDEX IDX_E2E2D1EE4E7C18CB ON commune (epci_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune DROP FOREIGN KEY FK_E2E2D1EE4E7C18CB');
        $this->addSql('DROP TABLE epci');
        $this->addSql('DROP INDEX IDX_E2E2D1EE4E7C18CB ON commune');
        $this->addSql('ALTER TABLE commune DROP epci_id');
    }
}
