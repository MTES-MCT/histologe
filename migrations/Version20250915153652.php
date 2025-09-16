<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250915153652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign Marseille communes to the correct EPCI';
    }

    public function up(Schema $schema): void
    {
        $epciId = $this->connection->fetchOne("SELECT id FROM epci WHERE code = '200054807'");
        $territoryId = $this->connection->fetchOne("SELECT id FROM territory WHERE zip = '13'");
        if ($epciId) {
            $this->addSql("UPDATE commune SET epci_id = ? WHERE nom LIKE 'Marseille%' AND territory_id = ?", [$epciId, $territoryId]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
