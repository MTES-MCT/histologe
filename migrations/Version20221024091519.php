<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221024091519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET motif_cloture = "RESOLU" WHERE motif_cloture="Problème résolu"');
    }

    public function down(Schema $schema): void
    {
    }
}
