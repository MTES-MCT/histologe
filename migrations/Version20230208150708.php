<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230208150708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns for new criticite algo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE critere ADD new_coef INT, ADD type INT');
        $this->addSql('ALTER TABLE criticite ADD new_score INT');
        $this->addSql('ALTER TABLE signalement ADD new_score_creation DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE critere DROP new_coef, DROP type');
        $this->addSql('ALTER TABLE criticite DROP new_score');
        $this->addSql('ALTER TABLE signalement DROP new_score_creation');
    }
}
