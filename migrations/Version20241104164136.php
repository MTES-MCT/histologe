<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241104164136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add type to Zone entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD type VARCHAR(255) NOT NULL DEFAULT \'AUTRE\' COMMENT \'Value possible enum ZoneType\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP type');
    }
}
