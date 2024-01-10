<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240104143200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on commune.code_postal/commune.code_insee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX code_postal_code_insee_unique ON commune (code_postal, code_insee)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX code_postal_code_insee_unique ON commune');
    }
}
