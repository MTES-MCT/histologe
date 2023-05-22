<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230519190612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add abandon_procedure to signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD is_usager_abandon_procedure BOOLEAN DEFAULT 0');
        $this->addSql('UPDATE signalement SET is_usager_abandon_procedure = 0 WHERE is_usager_abandon_procedure IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP is_usager_abandon_procedure');
    }
}
