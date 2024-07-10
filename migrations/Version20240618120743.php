<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240618120743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add synchro_data column to file and signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD synchro_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE partner CHANGE idoss_token_expiration_date idoss_token_expiration_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE signalement ADD synchro_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP synchro_data');
        $this->addSql('ALTER TABLE partner CHANGE idoss_token_expiration_date idoss_token_expiration_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement DROP synchro_data');
    }
}
