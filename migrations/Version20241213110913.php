<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241213110913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix invalid zone area';
    }

    public function up(Schema $schema): void
    {
        $zones = $this->connection->fetchAllAssociative('SELECT id, area FROM zone');
        foreach ($zones as $zone) {
            try {
                $testSQL = 'SELECT ST_GeomFromText(:area)';
                $this->connection->executeQuery($testSQL, ['area' => $zone['area']]);
            } catch (\Exception $e) {
                $this->addSql('UPDATE zone SET area = "POINT (3.880363 43.608965)" WHERE id = :id', ['id' => $zone['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
