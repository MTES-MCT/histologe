<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250207080229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete dernier_bail_at for signalement_qualification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_qualification DROP dernier_bail_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_qualification ADD dernier_bail_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
