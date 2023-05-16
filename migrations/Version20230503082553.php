<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230503082553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add creation_date and modification_date to partners';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP created_at, DROP updated_at');
    }
}
