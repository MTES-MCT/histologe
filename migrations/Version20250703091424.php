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
        $this->addSql('UPDATE desordre_categorie SET label_categorie = "Eclairage / hauteur" WHERE id = ' . $desordreCategoryId);

        // Add new desordre critere
        $this->addSql('
            INSERT INTO desordre_critere
            (desordre_categorie_id, slug_categorie, label_categorie, zone_categorie, slug_critere, label_critere, created_at)
            VALUES ('.$desordreCategoryId.', "desordres_logement_lumiere", "Eclairement, lumière naturelle", "LOGEMENT", "desordres_logement_lumiere_plafond_trop_bas", "Les plafonds sont trop bas", NOW())
        ');

        // Rename desordre criteres categorie label
        $this->addSql('UPDATE desordre_critere SET label_categorie = "Eclairement, lumière naturelle, hauteur sous plafond" WHERE label_categorie = "Eclairement, lumière naturelle"');
        
        // Add new desordre precisions for desordre critere 'desordres_logement_lumiere_plafond_trop_bas'
        $desordreCritereId = '(SELECT id FROM desordre_critere WHERE slug_critere = "desordres_logement_lumiere_plafond_trop_bas")';
        $insertIntoColumns = 'INSERT INTO desordre_precision (desordre_critere_id, coef, is_danger, is_suroccupation, is_insalubrite, label, qualification, desordre_precision_slug, created_at)';
        $this->addSql(
            $insertIntoColumns. ' VALUES ('.$desordreCritereId.', 3, 0, 0, 0, \'<span>Dans : <b>Une ou des pièces à vivre (salon, chambres)</b></span>\', \'["RSD", "NON_DECENCE"]\', \'desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre\', NOW())'
        );
        $this->addSql(
            $insertIntoColumns. ' VALUES ('.$desordreCritereId.', 3, 0, 0, 0, \'<span>Dans : <b>La cuisine / le coin cuisine</b></span>\', \'["RSD", "NON_DECENCE"]\', \'desordres_logement_lumiere_plafond_trop_bas_cuisine\', NOW())'
        );
        $this->addSql(
            $insertIntoColumns. ' VALUES ('.$desordreCritereId.', 3, 0, 0, 0, \'<span>Dans : <b>La salle de bain / salle d\\\'eau et ou les toilettes</b></span>\', \'["RSD", "NON_DECENCE"]\', \'desordres_logement_lumiere_plafond_trop_bas_salle_de_bain\', NOW())'
        );
        $this->addSql(
            $insertIntoColumns. ' VALUES ('.$desordreCritereId.', 3, 0, 0, 1, \'<span>Dans : <b>Toutes les pièces</b></span>\', \'["RSD", "NON_DECENCE", "INSALUBRITE"]\', \'desordres_logement_lumiere_plafond_trop_bas_toutes_pieces\', NOW())'
        );
        $this->addSql(
            $insertIntoColumns. ' VALUES ('.$desordreCritereId.', 3, 0, 0, 1, \'<span>Dans : <b>Toutes les pièces</b></span>\', \'["RSD", "NON_DECENCE", "INSALUBRITE"]\', \'desordres_logement_lumiere_plafond_trop_bas_piece_unique\', NOW())'
        );

        // migrer desordres_type_composition_logement_piece_unique_hauteur et desordres_type_composition_logement_plusieurs_pieces_hauteur
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DELETE FROM desordre_precision WHERE desordre_precision_slug IN (
                "desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre",
                "desordres_logement_lumiere_plafond_trop_bas_cuisine",
                "desordres_logement_lumiere_plafond_trop_bas_salle_de_bain",
                "desordres_logement_lumiere_plafond_trop_bas_toutes_pieces",
                "desordres_logement_lumiere_plafond_trop_bas_piece_unique"
            )
        ');
        $this->addSql('
            UPDATE desordre_critere SET label_categorie = "Eclairement, lumière naturelle" WHERE label_categorie = "Eclairement, lumière naturelle, hauteur sous plafond"
        ');
        $this->addSql('
            DELETE FROM desordre_critere WHERE slug_critere = "desordres_logement_lumiere_plafond_trop_bas"
        ');
    }
}
