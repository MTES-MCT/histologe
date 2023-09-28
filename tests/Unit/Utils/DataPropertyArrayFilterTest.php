<?php

namespace App\Tests\Unit\Utils;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Utils\DataPropertyArrayFilter;
use PHPUnit\Framework\TestCase;

class DataPropertyArrayFilterTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testFilterByPrefix(array $prefixes, array $filteredDataExpected): void
    {
        $data = json_decode(
            file_get_contents(__DIR__.'/../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        $filteredData = DataPropertyArrayFilter::filterByPrefix($data, $prefixes);

        $this->assertEquals($filteredDataExpected, $filteredData);
    }

    public function provideData(): \Generator
    {
        yield 'Données Type composition' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_TYPE_COMPOSITION,
            [
                'bail_dpe_dpe' => 'oui',
                'bail_dpe_bail' => 'oui',
                'type_logement_rdc' => 'non',
                'type_logement_nature' => 'appartement',
                'bail_dpe_etat_des_lieux' => 'oui',
                'bail_dpe_date_emmenagement' => '2020-10-01',
                'type_logement_commodites_wc' => 'oui',
                'type_logement_dernier_etage' => 'non',
                'composition_logement_enfants' => 'oui',
                'composition_logement_nb_pieces' => '2',
                'composition_logement_superficie' => '45',
                'type_logement_commodites_cuisine' => 'oui',
                'composition_logement_piece_unique' => 'plusieurs_pieces',
                'type_logement_commodites_wc_cuisine' => 'non',
                'type_logement_sous_sol_sans_fenetre' => 'non',
                'composition_logement_nombre_personnes' => '3',
                'type_logement_commodites_salle_de_bain' => 'oui',
                'type_logement_commodites_wc_hauteur_plafond' => 'oui',
                'type_logement_pieces_a_vivre_hauteur_piece_1' => 'oui',
                'type_logement_pieces_a_vivre_hauteur_piece_2' => 'oui',
                'type_logement_pieces_a_vivre_superficie_piece_1' => '20',
                'type_logement_pieces_a_vivre_superficie_piece_2' => '15',
                'type_logement_commodites_cuisine_hauteur_plafond' => 'oui',
                'type_logement_commodites_salle_de_bain_collective' => 'oui',
                'type_logement_commodites_salle_de_bain_hauteur_plafond' => 'oui',
            ],
        ];

        yield 'Données Situation Foyer' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_SITUATION_FOYER,
            [
                'logement_social_allocation' => 'oui',
                'logement_social_date_naissance' => '2018-10-01',
                'logement_social_allocation_caisse' => 'caf',
                'travailleur_social_accompagnement' => 'oui',
                'logement_social_demande_relogement' => 'oui',
                'logement_social_montant_allocation' => '300',
                'logement_social_numero_allocataire' => '12345678',
                'travailleur_social_quitte_logement' => 'non',
            ],
        ];

        yield 'Données Procedure' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_PROCEDURE,
            [
                'utilisation_service_ok_visite' => 1,
                'info_procedure_bailleur_prevenu' => 'oui',
                'info_procedure_assurance_contactee' => 'non',
                'info_procedure_depart_apres_travaux' => 'oui',
                'utilisation_service_ok_demande_logement' => 1,
                'utilisation_service_ok_prevenir_bailleur' => 1,
            ],
        ];

        yield 'Données Information complémentaire' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_COMPLEMENTAIRE,
            [
                'informations_complementaires_logement_montant_loyer' => '500',
                'informations_complementaires_logement_nombre_etages' => '5',
                'informations_complementaires_logement_annee_construction' => '1970-02-10',
                'informations_complementaires_situation_occupants_beneficiaire_fsl' => 'non',
                'informations_complementaires_situation_occupants_beneficiaire_rsa' => 'non',
            ],
        ];
    }
}
