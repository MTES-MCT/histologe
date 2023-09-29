<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230929142816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace Esabora by SI-SH in suivis from SI-SH';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;
        $sql = "UPDATE suivi SET description = REPLACE(description, 'Esabora', 'SI-SH')
                WHERE description LIKE '%Esabora%'
                  AND is_public = 0
                  AND type = 1
                  AND EXISTS (
                      SELECT 1
                      FROM user u
                      INNER JOIN partner p ON u.partner_id = p.id
                      WHERE u.id = suivi.created_by_id
                      AND p.type = 'ARS'
                  )";

        $connection->executeQuery($sql);
    }

    public function down(Schema $schema): void
    {
    }
}
