<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230214155156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns for new criticite algo.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE critere ADD new_coef INT NOT NULL, ADD type INT NOT NULL');
        $this->addSql('ALTER TABLE criticite ADD new_score DOUBLE PRECISION NOT NULL, ADD is_danger TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE signalement ADD new_score_creation DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE critere DROP new_coef, DROP type');
        $this->addSql('ALTER TABLE criticite DROP new_score, DROP is_danger');
        $this->addSql('ALTER TABLE signalement DROP new_score_creation');
    }
}
