<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722161243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change score_logement and score_batiment columns to be NOT NULL in signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET score_logement = 0 WHERE score_logement IS NULL');
        $this->addSql('UPDATE signalement SET score_batiment = 0 WHERE score_batiment IS NULL');
        $this->addSql('ALTER TABLE signalement CHANGE score_logement score_logement DOUBLE PRECISION NOT NULL, CHANGE score_batiment score_batiment DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement CHANGE score_logement score_logement DOUBLE PRECISION DEFAULT NULL, CHANGE score_batiment score_batiment DOUBLE PRECISION DEFAULT NULL');
    }
}
