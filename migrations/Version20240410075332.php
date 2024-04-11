<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240410075332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at and deleted_by_id on suivi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD deleted_by_id INT DEFAULT NULL, ADD deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8FC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2EBCCA8FC76F1F52 ON suivi (deleted_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP FOREIGN KEY FK_2EBCCA8FC76F1F52');
        $this->addSql('DROP INDEX IDX_2EBCCA8FC76F1F52 ON suivi');
        $this->addSql('ALTER TABLE suivi DROP deleted_by_id, DROP deleted_at');
    }
}
