<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250204095010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and updated_at fields on Zone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE type type VARCHAR(255) NOT NULL COMMENT \'Value possible enum ZoneType\'');
        $this->addSql("
            UPDATE zone z
            INNER JOIN history_entry h ON z.id = h.entity_id
            SET z.created_at = h.created_at
            WHERE h.entity_name LIKE '%Zone'
              AND h.event LIKE 'CREATE'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP created_at, DROP updated_at, CHANGE type type VARCHAR(255) DEFAULT \'AUTRE\' NOT NULL COMMENT \'Value possible enum ZoneType\'');
    }
}
