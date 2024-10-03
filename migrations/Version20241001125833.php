<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241001125833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make signalement column nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file CHANGE signalement_id signalement_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file CHANGE signalement_id signalement_id INT NOT NULL');
    }
}
