<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230214155156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make new_coef, type, new_score and is_danger not nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE critere CHANGE new_coef new_coef INT NOT NULL, CHANGE type type INT NOT NULL');
        $this->addSql('ALTER TABLE criticite CHANGE new_score new_score DOUBLE PRECISION NOT NULL, CHANGE is_danger is_danger TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE critere CHANGE new_coef new_coef INT DEFAULT NULL, CHANGE type type INT DEFAULT NULL');
        $this->addSql('ALTER TABLE criticite CHANGE new_score new_score DOUBLE PRECISION DEFAULT NULL, CHANGE is_danger is_danger TINYINT(1) DEFAULT NULL');
    }
}
