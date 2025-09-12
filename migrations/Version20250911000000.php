<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne parent_file_id à la table File';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD parent_file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610F92F3E70 FOREIGN KEY (parent_file_id) REFERENCES file (id)');
        $this->addSql('CREATE INDEX IDX_8C9F3610F92F3E70 ON file (parent_file_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F3610F92F3E70');
        $this->addSql('DROP INDEX IDX_8C9F3610F92F3E70 ON file');
        $this->addSql('ALTER TABLE file DROP parent_file_id');
    }
}