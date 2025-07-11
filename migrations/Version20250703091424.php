<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250703091424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename desordre categorie, add new desordre critere, add new desordre precision';
    }

    public function up(Schema $schema): void
    {
        // Rename desordre category
        $desordreCategoryId = $this->connection->fetchOne('SELECT id FROM desordre_categorie WHERE label = "Eclairage"');
        if ($desordreCategoryId) {
            $this->addSql('UPDATE desordre_categorie SET label = "Eclairage / hauteur" WHERE id = '.$desordreCategoryId);

            // Add new desordre critere
            $this->addSql('
                INSERT INTO desordre_critere
                (desordre_categorie_id, slug_categorie, label_categorie, zone_categorie, slug_critere, label_critere, created_at)
                VALUES ('.$desordreCategoryId.', "desordres_logement_lumiere", "Eclairement, lumière naturelle", "LOGEMENT", "desordres_logement_lumiere_plafond_trop_bas", "Les plafonds sont trop bas", NOW())
            ');

            // Rename desordre criteres categorie label
            $this->addSql('UPDATE desordre_critere SET label_categorie = "Eclairement, lumière naturelle, hauteur sous plafond" WHERE label_categorie = "Eclairement, lumière naturelle"');

            // Add new desordre precisions for desordre critere 'desordres_logement_lumiere_plafond_trop_bas'
            $desordreCritereIdSubquery = '(SELECT id FROM desordre_critere WHERE slug_critere = "desordres_logement_lumiere_plafond_trop_bas")';
            $insertIntoColumns = 'INSERT INTO desordre_precision (desordre_critere_id, coef, is_danger, is_suroccupation, is_insalubrite, label, qualification, desordre_precision_slug, created_at)';
            $this->addSql(
                $insertIntoColumns.' VALUES ('.$desordreCritereIdSubquery.', 3, 0, 0, 0, \'<span>Dans : <b>Une ou des pièces à vivre (salon, chambres)</b></span>\', \'["RSD", "NON_DECENCE"]\', \'desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre\', NOW())'
            );
            $this->addSql(
                $insertIntoColumns.' VALUES ('.$desordreCritereIdSubquery.', 3, 0, 0, 0, \'<span>Dans : <b>La cuisine / le coin cuisine</b></span>\', \'["RSD", "NON_DECENCE"]\', \'desordres_logement_lumiere_plafond_trop_bas_cuisine\', NOW())'
            );
            $this->addSql(
                $insertIntoColumns.' VALUES ('.$desordreCritereIdSubquery.', 3, 0, 0, 0, \'<span>Dans : <b>La salle de bain / La salle d\\\'eau et/ou les toilettes</b></span>\', \'["RSD", "NON_DECENCE"]\', \'desordres_logement_lumiere_plafond_trop_bas_salle_de_bain\', NOW())'
            );
            $this->addSql(
                $insertIntoColumns.' VALUES ('.$desordreCritereIdSubquery.', 3, 0, 0, 1, \'<span>Dans : <b>Toutes les pièces</b></span>\', \'["RSD", "NON_DECENCE", "INSALUBRITE"]\', \'desordres_logement_lumiere_plafond_trop_bas_toutes_pieces\', NOW())'
            );
        }

        // get list of signalement linked to desordre precision with desordres_type_composition_logement_piece_unique_hauteur or desordres_type_composition_logement_plusieurs_pieces_hauteur
        $signalementIds = $this->connection->fetchFirstColumn('
            SELECT DISTINCT dps.signalement_id FROM desordre_precision_signalement dps WHERE dps.desordre_precision_id IN (
                SELECT id FROM desordre_precision WHERE desordre_precision_slug IN (
                    "desordres_type_composition_logement_piece_unique_hauteur",
                    "desordres_type_composition_logement_plusieurs_pieces_hauteur"
                )
            )
        ');
        if (!empty($signalementIds)) {
            $signalementIdsTableSql = '('.implode(' UNION ALL ', array_map(
                fn ($id) => 'SELECT '.$id.' AS signalement_id',
                $signalementIds
            )).')';
            $signalementIdsIn = implode(',', $signalementIds);

            $this->addSql('
                INSERT INTO desordre_critere_signalement (desordre_critere_id, signalement_id)
                SELECT dp.desordre_critere_id, s.signalement_id
                FROM desordre_precision dp
                JOIN '.$signalementIdsTableSql.' s
                WHERE dp.desordre_precision_slug = "desordres_logement_lumiere_plafond_trop_bas_toutes_pieces"
                  AND NOT EXISTS (
                    SELECT 1 FROM desordre_critere_signalement dcs
                    WHERE dcs.desordre_critere_id = dp.desordre_critere_id
                      AND dcs.signalement_id = s.signalement_id
                  )
            ');
            $this->addSql('
                INSERT INTO desordre_precision_signalement (desordre_precision_id, signalement_id)
                SELECT dp.id, s.signalement_id
                FROM desordre_precision dp
                JOIN '.$signalementIdsTableSql.' s
                WHERE dp.desordre_precision_slug = "desordres_logement_lumiere_plafond_trop_bas_toutes_pieces"
                  AND NOT EXISTS (
                    SELECT 1 FROM desordre_precision_signalement dps2
                    WHERE dps2.desordre_precision_id = dp.id
                      AND dps2.signalement_id = s.signalement_id
                  )
            ');
            $this->addSql('
                DELETE FROM desordre_precision_signalement WHERE desordre_precision_id IN (
                    SELECT id FROM desordre_precision WHERE desordre_precision_slug IN (
                        "desordres_type_composition_logement_piece_unique_hauteur",
                        "desordres_type_composition_logement_plusieurs_pieces_hauteur"
                    )
                ) AND signalement_id IN ('.$signalementIdsIn.')
            ');
            $this->addSql('
                DELETE FROM desordre_critere_signalement WHERE desordre_critere_id IN (
                    SELECT desordre_critere_id FROM desordre_precision WHERE desordre_precision_slug IN (
                        "desordres_type_composition_logement_piece_unique_hauteur",
                        "desordres_type_composition_logement_plusieurs_pieces_hauteur"
                    )
                ) AND signalement_id IN ('.$signalementIdsIn.')
            ');
        }
    }

    public function down(Schema $schema): void
    {
        // Delete all precisions linked to critère, then delete critère
        $this->addSql('
            DELETE FROM desordre_precision WHERE desordre_critere_id = (
                SELECT id FROM desordre_critere WHERE slug_critere = "desordres_logement_lumiere_plafond_trop_bas"
            )
        ');
        $this->addSql('
            UPDATE desordre_critere SET label_categorie = "Eclairement, lumière naturelle" WHERE label_categorie = "Eclairement, lumière naturelle, hauteur sous plafond"
        ');
        $this->addSql('
            DELETE FROM desordre_critere WHERE slug_critere = "desordres_logement_lumiere_plafond_trop_bas"
        ');
        $this->addSql('UPDATE desordre_categorie SET label = "Eclairage" WHERE label = "Eclairage / hauteur"');
    }
}
