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
        $sql = "UPDATE $tableName
                SET geoloc = JSON_SET(
                    geoloc,
                    '$.lat',
                    JSON_UNQUOTE(JSON_EXTRACT(geoloc, '$.lng')),
                    '$.lng',
                    JSON_UNQUOTE(JSON_EXTRACT(geoloc, '$.lat'))
                )
                WHERE geoloc IS NOT NULL";
        $connection->executeQuery($sql);
    }

    public function down(Schema $schema): void
    {
    }
}
