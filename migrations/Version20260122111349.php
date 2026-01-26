<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260122111349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timestampable fields table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql(
            'UPDATE auto_affectation_rule 
             SET created_at = NOW(), updated_at = NOW()'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule DROP created_at, DROP updated_at');
    }
}
