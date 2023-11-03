<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231031132907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add field motif_refus to affectation and signalement tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation ADD motif_refus VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD motif_refus VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation DROP motif_refus');
        $this->addSql('ALTER TABLE signalement DROP motif_refus');
    }
}
