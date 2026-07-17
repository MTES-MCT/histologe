<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716153742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix date_entree with a 2-digit truncated year (e.g. 0022-01-01 -> 2022-01-01)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE signalement
            SET date_entree = CASE
                WHEN DATE_ADD(date_entree, INTERVAL 2000 YEAR) <= created_at
                    THEN DATE_ADD(date_entree, INTERVAL 2000 YEAR)
                ELSE DATE_ADD(date_entree, INTERVAL 1900 YEAR)
            END
            WHERE date_entree < '1900-01-01'
                AND YEAR(date_entree) BETWEEN 10 AND 99
                AND (creation_source IS NULL OR creation_source != 'IMPORT')
                AND (
                    DATE_ADD(date_entree, INTERVAL 2000 YEAR) <= created_at
                    OR YEAR(DATE_ADD(date_entree, INTERVAL 2000 YEAR)) > YEAR(created_at)
                )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
