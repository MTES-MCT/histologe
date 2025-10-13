<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251013111836 extends AbstractMigration
{
    private const array SLUGS_IS_UNIQUE = [
        'desordres_batiment_isolation_dernier_etage_toit_sous_toit_non',
        'desordres_batiment_isolation_dernier_etage_toit_dernier_etage',
        'desordres_batiment_isolation_dernier_etage_toit_sous_combles',
        'desordres_batiment_isolation_dernier_etage_toit_maison_individuelle',
        'desordres_batiment_isolation_infiltration_eau_au_sol_non',
        'desordres_batiment_isolation_infiltration_eau_sous_sol',
        'desordres_batiment_isolation_infiltration_eau_rdc',
        'desordres_batiment_isolation_infiltration_eau_maison_individuelle',
        'desordres_batiment_securite_murs_fissures_details_mur_porteur_oui',
        'desordres_batiment_securite_murs_fissures_details_mur_porteur_non',
        'desordres_batiment_securite_murs_fissures_details_mur_porteur_nsp',
        'desordres_type_composition_logement_cuisine_collective_oui',
        'desordres_type_composition_logement_cuisine_collective_non',
        'desordres_type_composition_logement_douche_collective_oui',
        'desordres_type_composition_logement_douche_collective_non',
        'desordres_type_composition_logement_wc_collectif_oui',
        'desordres_type_composition_logement_wc_collectif_non',
        'desordres_type_composition_logement_wc_cuisine_ensemble',
        'desordres_type_composition_logement_suroccupation_non_allocataire',
        'desordres_type_composition_logement_suroccupation_allocataire',
        'desordres_logement_aeration_aucune_aeration_pieces_tout',
        'desordres_logement_aeration_ventilation_defectueuse_details_pieces_tout_nettoyage_oui',
        'desordres_logement_aeration_ventilation_defectueuse_details_pieces_tout_nettoyage_non',
        'desordres_logement_aeration_ventilation_defectueuse_details_pieces_tout_nettoyage_nsp',
        'desordres_logement_chauffage_details_fenetres_permeables_pieces_tout',
        'desordres_logement_chauffage_type_aucun_pieces_tout',
        'desordres_logement_chauffage_details_difficultes_chauffage_pieces_tout',
        'desordres_logement_chauffage_details_chauffage_KO_pieces_tout',
        'desordres_logement_securite_sol_glissant_pieces_tout',
        'desordres_logement_securite_sol_dangereux_pieces_tout',
        'desordres_logement_securite_balcons_pieces_tout',
        'desordres_logement_securite_plomb_pieces_tout_diagnostique_oui',
        'desordres_logement_securite_plomb_pieces_tout_diagnostique_non',
        'desordres_logement_securite_plomb_pieces_tout_diagnostique_nsp',
        'desordres_logement_electricite_manque_prises_details_multiprises_oui',
        'desordres_logement_electricite_manque_prises_details_multiprises_non',
        'desordres_logement_electricite_manque_prises_details_multiprises_nsp',
        'desordres_logement_nuisibles_cafards_details_date_before_movein',
        'desordres_logement_nuisibles_cafards_details_date_after_movein',
        'desordres_logement_nuisibles_cafards_details_date_nsp',
        'desordres_logement_nuisibles_rongeurs_details_date_before_movein',
        'desordres_logement_nuisibles_rongeurs_details_date_after_movein',
        'desordres_logement_nuisibles_rongeurs_details_date_nsp',
        'desordres_logement_nuisibles_punaises_details_date_before_movein',
        'desordres_logement_nuisibles_punaises_details_date_after_movein',
        'desordres_logement_nuisibles_punaises_details_date_nsp',
        'desordres_logement_nuisibles_autres_details_date_before_movein',
        'desordres_logement_nuisibles_autres_details_date_after_movein',
        'desordres_logement_nuisibles_autres_details_date_nsp',
        'desordres_logement_lumiere_pas_lumiere_pieces_tout',
        'desordres_logement_lumiere_pas_volets_pieces_tout',
        'desordres_logement_lumiere_plafond_trop_bas_toutes_pieces',
        'desordres_logement_proprete_pieces_tout',
    ];

    private const array SLUGS_IS_PRECISION_LIBRE = [
        'desordres_batiment_nuisibles_autres' => 'Précisez le type de nuisible',
        'desordres_logement_nuisibles_autres' => 'Précisez le type de nuisible', // ca va pas !!!!
        'desordres_logement_lumiere_plafond_trop_bas_cuisine' => 'Précisez la hauteur du plafond (en cm)',
        'desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre' => 'Précisez la hauteur du plafond (en cm)',
        'desordres_logement_lumiere_plafond_trop_bas_salle_de_bain' => 'Précisez la hauteur du plafond (en cm)',
        'desordres_logement_lumiere_plafond_trop_bas_toutes_pieces' => 'Précisez la hauteur du plafond (en cm)',
    ];

    public function getDescription(): string
    {
        return 'Add config columns and set config values to desordre_precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_precision ADD config_is_unique TINYINT(1) NOT NULL');
        foreach (self::SLUGS_IS_UNIQUE as $slug) {
            $this->addSql('UPDATE desordre_precision SET config_is_unique = 1 WHERE desordre_precision_slug = ?', [$slug]);
        }
        /*$this->addSql('ALTER TABLE desordre_precision ADD config_precision_libre VARCHAR(255) DEFAULT NULL');
        foreach (self::SLUGS_IS_PRECISION_LIBRE as $slug => $label) {
            $this->addSql('UPDATE desordre_precision SET config_precision_libre = ? WHERE desordre_precision_slug = ?', [$label, $slug]);
        }*/
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_precision DROP config_is_unique');
        $this->addSql('ALTER TABLE desordre_precision DROP config_precision_libre');
    }
}
