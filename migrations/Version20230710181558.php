<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230710181558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add intervention column to file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD intervention_id INT DEFAULT NULL AFTER signalement_id');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36108EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('CREATE INDEX IDX_8C9F36108EAE3863 ON file (intervention_id)');
        $this->addSql('ALTER TABLE file RENAME INDEX idx_8c9f3610a76ed395 TO IDX_8C9F3610A2B28FE8');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36108EAE3863');
        $this->addSql('DROP INDEX IDX_8C9F36108EAE3863 ON file');
        $this->addSql('ALTER TABLE file DROP intervention_id');
        $this->addSql('ALTER TABLE file RENAME INDEX idx_8c9f3610a2b28fe8 TO IDX_8C9F3610A76ED395');
    }
}
