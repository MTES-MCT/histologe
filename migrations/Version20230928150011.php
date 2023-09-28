<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230928150011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Invert latitude and longitude in signalement.geoloc';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;
        $tableName = 'signalement';
        $sql = "SELECT id, geoloc FROM $tableName WHERE geoloc IS NOT NULL";
        $rows = $connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($rows as $row) {
            $geolocData = json_decode($row['geoloc'], true);
            if ($geolocData && isset($geolocData['lat'], $geolocData['lng'])) {
                $geolocData = ['lat' => $geolocData['lng'], 'lng' => $geolocData['lat']];
                $updatedGeoloc = json_encode($geolocData);
                $updateSql = "UPDATE $tableName SET geoloc = :geoloc WHERE id = :id";
                $connection->executeStatement($updateSql, ['geoloc' => $updatedGeoloc, 'id' => $row['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
