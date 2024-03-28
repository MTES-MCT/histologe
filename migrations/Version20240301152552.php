<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240301152552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_waiting_suivi column to file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD is_waiting_suivi TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP is_waiting_suivi');
    }
}
