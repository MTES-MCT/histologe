<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427124751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modification et création d\'index pour améliorer les performances du dashboard';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_affectation_statut_partner_sig ON affectation');
        $this->addSql('CREATE INDEX idx_affectation_sig_statut_partner ON affectation (signalement_id, statut, partner_id)');
        $this->addSql('CREATE INDEX idx_signalement_statut_created_by ON signalement (statut, created_by_id)');
        $this->addSql('CREATE INDEX idx_signalement_is_usager_abandon_procedure ON signalement (is_usager_abandon_procedure)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_affectation_sig_statut_partner ON affectation');
        $this->addSql('CREATE INDEX idx_affectation_statut_partner_sig ON affectation (statut, partner_id, signalement_id)');
        $this->addSql('DROP INDEX idx_signalement_statut_created_by ON signalement');
        $this->addSql('DROP INDEX idx_signalement_is_usager_abandon_procedure ON signalement');
    }
}
