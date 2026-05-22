<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519143307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert zone.area to GEOMETRY type and add spatial index for performance optimization';
    }

    public function up(Schema $schema): void
    {
        // Add temporary column for geometry data
        $this->addSql('ALTER TABLE zone ADD COLUMN area_geom GEOMETRY NULL');

        // Convert WKT text to GEOMETRY
        $this->addSql('UPDATE zone SET area_geom = ST_GeomFromText(area)');

        // Drop old TEXT column
        $this->addSql('ALTER TABLE zone DROP COLUMN area');

        // Rename geometry column to original name with NOT NULL and default POINT(0 0)
        // Default value allows Doctrine to insert without area (insertable=false)
        // ZoneGeometryPersistListener will UPDATE with correct GEOMETRY value immediately after
        $this->addSql("ALTER TABLE zone CHANGE COLUMN area_geom area GEOMETRY NOT NULL DEFAULT (ST_GeomFromText('POINT(0 0)'))");

        // Add spatial index for ST_Contains performance
        $this->addSql('ALTER TABLE zone ADD SPATIAL INDEX idx_area_spatial (area)');
    }

    public function down(Schema $schema): void
    {
        // Remove spatial index
        $this->addSql('ALTER TABLE zone DROP INDEX idx_area_spatial');

        // Add temporary LONGTEXT column (LONGTEXT to support large WKT geometries)
        $this->addSql('ALTER TABLE zone ADD COLUMN area_text LONGTEXT NULL');

        // Convert GEOMETRY back to WKT text
        $this->addSql('UPDATE zone SET area_text = ST_AsText(area)');

        // Drop GEOMETRY column
        $this->addSql('ALTER TABLE zone DROP COLUMN area');

        // Rename text column to original name
        $this->addSql('ALTER TABLE zone CHANGE COLUMN area_text area LONGTEXT NOT NULL');
    }
}
