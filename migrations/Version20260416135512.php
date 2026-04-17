<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416135512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'indexs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_affectation_statut_partner_sig ON affectation (statut, partner_id, signalement_id)');
        $this->addSql('CREATE INDEX idx_partner_type ON partner (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_affectation_statut_partner_sig ON affectation');
        $this->addSql('DROP INDEX idx_partner_type ON partner');
    }
}
