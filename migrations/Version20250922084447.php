<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250922084447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix commodites collectives: set individual commodities to "non" when collective versions are "oui"';
    }

    public function up(Schema $schema): void
    {
        // Fix WC: when type_logement_commodites_wc_collective is "oui", set type_logement_commodites_wc to "non"
        $this->addSql(<<<'SQL'
            UPDATE signalement s
            SET
                s.type_composition_logement = JSON_SET(s.type_composition_logement, '$.type_logement_commodites_wc', 'non')
            WHERE JSON_EXTRACT(type_composition_logement, '$.type_logement_commodites_wc_collective') = 'oui'
            AND created_by_id IS NOT NULL
        SQL);

        // Fix cuisine: when type_logement_commodites_cuisine_collective is "oui", set type_logement_commodites_cuisine to "non"
        $this->addSql(<<<'SQL'
            UPDATE signalement s
            SET
                s.type_composition_logement = JSON_SET(s.type_composition_logement, '$.type_logement_commodites_cuisine', 'non')
            WHERE JSON_EXTRACT(type_composition_logement, '$.type_logement_commodites_cuisine_collective') = 'oui'
            AND created_by_id IS NOT NULL
        SQL);

        // Fix salle de bain: when type_logement_commodites_salle_de_bain_collective is "oui", set type_logement_commodites_salle_de_bain to "non"
        $this->addSql(<<<'SQL'
            UPDATE signalement s
            SET
                s.type_composition_logement = JSON_SET(s.type_composition_logement, '$.type_logement_commodites_salle_de_bain', 'non')
            WHERE JSON_EXTRACT(type_composition_logement, '$.type_logement_commodites_salle_de_bain_collective') = 'oui'
            AND created_by_id IS NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
