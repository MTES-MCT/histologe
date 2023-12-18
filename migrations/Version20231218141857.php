<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231218141857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add score_logement and score_batiment to signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD score_logement DOUBLE PRECISION NOT NULL, ADD score_batiment DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP score_logement, DROP score_batiment');
    }
}
