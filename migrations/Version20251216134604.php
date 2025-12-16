<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216134604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add DesordreCritere desordres_logement_humidite_tout and 8 linked DesordrePrecision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO desordre_critere (
                desordre_categorie_id,
                slug_categorie,
                label_categorie,
                zone_categorie,
                slug_critere,
                label_critere,
                created_at
            )
            SELECT
                dc.id,
                'desordres_logement_humidite',
                'Humidité et moisissure',
                'LOGEMENT',
                'desordres_logement_humidite_tout',
                'Le logement est humide et a des traces de moisissures',
                NOW()
            FROM desordre_categorie dc
            WHERE dc.label = 'Aération / humidité'
            LIMIT 1
        ");

        $this->addSql("
            INSERT INTO desordre_precision (
                desordre_critere_id,
                coef,
                is_danger,
                is_suroccupation,
                is_insalubrite,
                label,
                qualification,
                desordre_precision_slug,
                created_at,
                config_is_unique
            )
            SELECT
                dct.id,
                dp.coef * 2,
                dp.is_danger,
                dp.is_suroccupation,
                dp.is_insalubrite,
                REPLACE(dp.label, 'Une ou des pièces à vivre (salon, chambres)', 'Tout le logement'),
                dp.qualification,
                REPLACE(
                    dp.desordre_precision_slug,
                    'desordres_logement_humidite_piece_a_vivre_',
                    'desordres_logement_humidite_tout_'
                ),
                NOW(),
                dp.config_is_unique
            FROM desordre_precision dp
            JOIN desordre_critere dcp ON dcp.id = dp.desordre_critere_id
            JOIN desordre_critere dct ON dct.slug_critere = 'desordres_logement_humidite_tout'
            WHERE dcp.slug_critere = 'desordres_logement_humidite_piece_a_vivre'
        ");

        $this->addSql("
            UPDATE desordre_precision
            SET
                is_danger = 0,
                qualification = '[\"NON_DECENCE\", \"RSD\"]',
                updated_at = NOW()
            WHERE desordre_precision_slug =
                'desordres_logement_humidite_piece_a_vivre_details_moisissure_apres_nettoyage_oui'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE dp
            FROM desordre_precision dp
            JOIN desordre_critere dc ON dc.id = dp.desordre_critere_id
            WHERE dc.slug_critere = 'desordres_logement_humidite_tout'
        ");

        $this->addSql("
            DELETE FROM desordre_critere
            WHERE slug_critere = 'desordres_logement_humidite_tout'
        ");

        $this->addSql("
            UPDATE desordre_precision
            SET
                is_danger = 1,
                qualification = '[\"NON_DECENCE\", \"RSD\", \"MISE_EN_SECURITE_PERIL\"]',
                updated_at = NOW()
            WHERE desordre_precision_slug =
                'desordres_logement_humidite_piece_a_vivre_details_moisissure_apres_nettoyage_oui'
        ");
    }
}
