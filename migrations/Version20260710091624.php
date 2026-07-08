<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710091624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changed date to datetime for more accurate sorting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete CHANGE imported_at imported_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete CHANGE imported_at imported_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
