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
        // Obtenez la connexion Doctrine
        $connection = $this->connection;

        // Nom de votre table
        $tableName = 'signalement';

        // Récupérez tous les enregistrements qui ont des données dans la colonne 'geoloc'
        $sql = "SELECT id, geoloc FROM $tableName WHERE geoloc IS NOT NULL";
        $rows = $connection->executeQuery($sql)->fetchAllAssociative();

        // Parcourez les enregistrements et inversez les valeurs 'lat' et 'lng' dans la colonne 'geoloc'
        foreach ($rows as $row) {
            $geolocData = json_decode($row['geoloc'], true);
            if ($geolocData && isset($geolocData['lat'], $geolocData['lng'])) {
                $geolocData = ['lat' => $geolocData['lng'], 'lng' => $geolocData['lat']];
                $updatedGeoloc = json_encode($geolocData);

                // Mettez à jour l'enregistrement avec la nouvelle valeur de 'geoloc'
                $updateSql = "UPDATE $tableName SET geoloc = :geoloc WHERE id = :id";
                $connection->executeStatement($updateSql, ['geoloc' => $updatedGeoloc, 'id' => $row['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
