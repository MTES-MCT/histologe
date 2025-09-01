<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes partner_competence et partner_type sur File';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD partner_competence LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE file ADD partner_type LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP partner_competence');
        $this->addSql('ALTER TABLE file DROP partner_type');
    }
}
