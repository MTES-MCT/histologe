<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019134428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add additional_information, arrete_creation_source, and signalement_id to arrete table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete ADD additional_information JSON DEFAULT NULL, ADD arrete_creation_source VARCHAR(255) DEFAULT NULL, ADD signalement_id INT DEFAULT NULL, ADD provider_id INT DEFAULT NULL COMMENT \'Unique id used by the provider\'');
        $this->addSql("UPDATE arrete SET additional_information = JSON_OBJECT(), arrete_creation_source = 'IMPORT'");
        $this->addSql('ALTER TABLE arrete MODIFY additional_information JSON NOT NULL, MODIFY arrete_creation_source VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE arrete ADD CONSTRAINT FK_8D9860A65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('CREATE INDEX IDX_8D9860A65C5E57E ON arrete (signalement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete DROP FOREIGN KEY FK_8D9860A65C5E57E');
        $this->addSql('DROP INDEX IDX_8D9860A65C5E57E ON arrete');
        $this->addSql('ALTER TABLE arrete DROP additional_information, DROP arrete_creation_source, DROP signalement_id, DROP provider_id');
    }
}
