<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250729071740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint to affectation table for signalement and partner';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(!in_array($_ENV['APP_ENV'], ['test', 'dev']), 'This migration should only run in test or dev environments');
        $this->addSql('CREATE UNIQUE INDEX unique_affectation_signalement_partner ON affectation (signalement_id, partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_affectation_signalement_partner ON affectation');
    }
}
