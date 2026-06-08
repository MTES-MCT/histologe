<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\Geometry\GeometryFactory;
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

        // Convert GEOMETRYCOLLECTION to MULTIPOLYGON in text format before converting to GEOMETRY
        $zones = $this->connection->fetchAllAssociative('SELECT id, area FROM zone');
        $geometryFactory = new GeometryFactory();

        foreach ($zones as $zone) {
            $wkt = $zone['area'];

            // Check if it's a GEOMETRYCOLLECTION
            if (str_starts_with(trim($wkt), 'GEOMETRYCOLLECTION')) {
                try {
                    // Use GeometryFactory to parse and simplify GEOMETRYCOLLECTION
                    $geometry = $geometryFactory->createFromWkt($wkt);
                    $newWkt = $geometryFactory->toWkt($geometry);

                    // Update the text column first
                    $this->addSql(
                        'UPDATE zone SET area = :wkt WHERE id = :id',
                        ['wkt' => $newWkt, 'id' => $zone['id']]
                    );
                } catch (\Exception $e) {
                    // Log or skip invalid geometries
                    $this->write(sprintf('Warning: Could not convert zone %d: %s', $zone['id'], $e->getMessage()));
                }
            }
        }

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
