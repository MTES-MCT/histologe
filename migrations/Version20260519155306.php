<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519155306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove context field from Suivi entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_suivi_context ON suivi');
        $this->addSql('ALTER TABLE suivi DROP context');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD context VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_suivi_context ON suivi (context)');
    }
}
