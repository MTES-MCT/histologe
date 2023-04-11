<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230403150414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'delete score_cloture and rename new_score_creation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement CHANGE new_score_creation score DOUBLE NOT NULL');
        $this->addSql('ALTER TABLE signalement DROP score_cloture');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD score_cloture DOUBLE PRECISION DEFAULT NULL, DROP score');
    }
}
