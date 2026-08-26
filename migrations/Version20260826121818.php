<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826121818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add travaux_mise_en_conformite_usager column to signalement table and change travaux_mise_en_conformite column length to 50';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD travaux_mise_en_conformite_usager VARCHAR(50) DEFAULT NULL, CHANGE travaux_mise_en_conformite travaux_mise_en_conformite VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP travaux_mise_en_conformite_usager, CHANGE travaux_mise_en_conformite travaux_mise_en_conformite VARCHAR(255) DEFAULT NULL');
    }
}
