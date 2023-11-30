<?php

namespace App\Factory\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Model\TypeCompositionLogement;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Utils\DataPropertyArrayFilter;

class TypeCompositionLogementFactory
{
    public function __construct(private SignalementDraftRequestSerializer $serializer)
    {
    }

    public function createFromSignalementDraftPayload(array $payload)
    {
        $data = DataPropertyArrayFilter::filterByPrefix(
            $payload,
            SignalementDraftRequest::PREFIX_PROPERTIES_TYPE_COMPOSITION
        );

        return $this->serializer->deserialize(json_encode($data), TypeCompositionLogement::class, 'json');
    }

    public static function createFromArray(array $data): TypeCompositionLogement
    {
        return new TypeCompositionLogement(
            typeLogementNature: $data['type_logement_nature'] ?? null,
            typeLogementRdc: $data['type_logement_rdc'] ?? null,
            typeLogementDernierEtage: $data['type_logement_dernier_etage'] ?? null,
            typeLogementSousSolSansFenetre: $data['type_logement_sous_sol_sans_fenetre'] ?? null,
            typeLogementSousCombleSansFenetre: $data['type_logement_sous_comble_sans_fenetre'] ?? null,
            typeLogementCommoditesCuisine: $data['type_logement_commodites_cuisine'] ?? null,
            typeLogementCommoditesPieceAVivre9m: $data['type_logement_commodites_piece_a_vivre_9m'] ?? null,
            typeLogementCommoditesCuisineCollective: $data['type_logement_commodites_cuisine_collective'] ?? null,
            typeLogementCommoditesSalleDeBain: $data['type_logement_commodites_salle_de_bain'] ?? null,
            typeLogementCommoditesSalleDeBainCollective: $data['type_logement_commodites_salle_de_bain_collective'] ?? null,
            typeLogementCommoditesWc: $data['type_logement_commodites_wc'] ?? null,
            typeLogementCommoditesWcCollective: $data['type_logement_commodites_wc_collective'] ?? null,
            typeLogementCommoditesWcCuisine: $data['type_logement_commodites_wc_cuisine'] ?? null,
            compositionLogementPieceUnique: $data['composition_logement_piece_unique'] ?? null,
            compositionLogementSuperficie: $data['composition_logement_superficie'] ?? null,
            compositionLogementHauteur: $data['composition_logement_hauteur'] ?? null,
            compositionLogementNbPieces: $data['composition_logement_nb_pieces'] ?? null,
            compositionLogementNombrePersonnes: $data['composition_logement_nombre_personnes'] ?? null,
            compositionLogementEnfants: $data['composition_logement_enfants'] ?? null,
            bailDpeBail: $data['bail_dpe_bail'] ?? null,
            bailDpeDpe: $data['bail_dpe_dpe'] ?? null,
            bailDpeEtatDesLieux: $data['bail_dpe_etat_des_lieux'] ?? null,
            bailDpeDateEmmenagement: $data['bail_dpe_date_emmenagement']
        );
    }
}
