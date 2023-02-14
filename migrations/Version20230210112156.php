<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230210112156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_danger on criticite';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE criticite ADD is_danger TINYINT(1) DEFAULT NULL,
        CHANGE new_score new_score DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE criticite DROP is_danger, CHANGE new_score new_score INT DEFAULT NULL');
    }
}
