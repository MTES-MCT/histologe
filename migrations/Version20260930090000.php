<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Empty geoloc for signalements without rnb_id_occupant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE signalement SET geoloc = JSON_ARRAY() WHERE rnb_id_occupant IS NULL OR rnb_id_occupant = ''");
    }

    public function down(Schema $schema): void
    {
    }
}
