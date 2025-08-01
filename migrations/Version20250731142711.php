<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250731142711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates the is_proprio_averti and information_procedure fields related to VISITE or VISITE_CONTROLE with the DONE status.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE signalement s
            INNER JOIN intervention i on i.signalement_id = s.id
            SET
                s.is_proprio_averti = true,
                s.information_procedure = JSON_SET(s.information_procedure, '$.info_procedure_bailleur_prevenu', 'oui')
            WHERE i.type IN ('VISITE', 'VISITE_CONTROLE')
              AND i.status LIKE 'DONE';
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE signalement s
            INNER JOIN intervention i on i.signalement_id = s.id
            SET
                s.is_proprio_averti = false,
                s.information_procedure = JSON_REMOVE(s.information_procedure, '$.info_procedure_bailleur_prevenu')
            WHERE i.type IN ('VISITE', 'VISITE_CONTROLE')
              AND i.status LIKE 'DONE';
        SQL);
    }
}
