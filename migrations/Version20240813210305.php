<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240813210305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert intervention dates from local to UTC using timezone from territoire table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE intervention i
            INNER JOIN signalement s ON s.id = i.signalement_id
            INNER JOIN territory t ON t.id = s.territory_id
            SET i.scheduled_at = CONVERT_TZ(i.scheduled_at, t.timezone, 'UTC');"
        );
    }

    public function down(Schema $schema): void
    {
    }
}
