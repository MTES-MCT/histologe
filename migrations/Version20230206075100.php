<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230206075100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "type" to Suivi to differenciate suivi from partners, suivi from usagers and automatical suivi ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD type INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP type');
    }
}
