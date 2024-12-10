<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241209082922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rnb_id_occupant column to signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP INDEX UNIQ_F4B551143EA4CB4D, ADD INDEX IDX_F4B551143EA4CB4D (created_from_id)');
        $this->addSql('ALTER TABLE signalement ADD rnb_id_occupant VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP INDEX IDX_F4B551143EA4CB4D, ADD UNIQUE INDEX UNIQ_F4B551143EA4CB4D (created_from_id)');
        $this->addSql('ALTER TABLE signalement DROP rnb_id_occupant');
    }
}
