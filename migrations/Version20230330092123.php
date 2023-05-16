<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230330092123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rendre is_commune nullable (supprimer ?)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner CHANGE is_commune is_commune TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner CHANGE is_commune is_commune TINYINT(1) NOT NULL');
    }
}
