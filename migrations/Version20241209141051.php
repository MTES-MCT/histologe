<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241209141051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bailleur_id to partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD bailleur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E1657B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E1657B5D0A2');
        $this->addSql('ALTER TABLE partner DROP bailleur_id');
    }
}
