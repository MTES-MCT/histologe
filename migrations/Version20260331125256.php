<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331125256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'in Signalement table, isLogementVacant should be false if it is null and if we have data for profileOccupant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET is_logement_vacant = false WHERE is_logement_vacant IS NULL AND profile_occupant IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
