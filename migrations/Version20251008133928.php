<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251008133928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Copy denomination_proprio to nom_proprio if nom_proprio is null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET nom_proprio = denomination_proprio WHERE denomination_proprio IS NOT NULL AND nom_proprio IS NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
