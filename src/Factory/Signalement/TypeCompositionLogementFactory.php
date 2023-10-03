<?php

namespace App\Factory\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Model\TypeCompositionLogement;
use App\Utils\DataPropertyArrayFilter;
use Symfony\Component\Serializer\SerializerInterface;

class TypeCompositionLogementFactory
{
    public function __construct(private SerializerInterface $serializer)
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
            typeLogementPiecesAVivreSuperficiePiece: $data['type_logement_pieces_a_vivre_superficie_piece'] ?? null,
            typeLogementPiecesAVivreHauteurPiece: $data['type_logement_pieces_a_vivre_hauteur_piece'] ?? null,
            typeLogementCommoditesCuisine: $data['type_logement_commodites_cuisine'] ?? null,
            typeLogementCommoditesCuisineCollective: $data['type_logement_commodites_cuisine_collective'] ?? null,
            typeLogementCommoditesCuisineHauteurPlafond: $data['type_logement_commodites_cuisine_hauteur_plafond'] ?? null,
            typeLogementCommoditesSalleDeBain: $data['type_logement_commodites_salle_de_bain'] ?? null,
            typeLogementCommoditesSalleDeBainCollective: $data['type_logement_commodites_salle_de_bain_collective'] ?? null,
            typeLogementCommoditesSalleDeBainHauteurPlafond: $data['type_logement_commodites_salle_de_bain_hauteur_plafond'] ?? null,
            typeLogementCommoditesWc: $data['type_logement_commodites_wc'] ?? null,
            typeLogementCommoditesWcCollective: $data['type_logement_commodites_wc_collective'] ?? null,
            typeLogementCommoditesWcHauteurPlafond: $data['type_logement_commodites_wc_hauteur_plafond'] ?? null,
            typeLogementCommoditesWcCuisine: $data['type_logement_commodites_wc_cuisine'] ?? null,
            compositionLogementPieceUnique: $data['composition_logement_piece_unique'] ?? null,
            compositionLogementSuperficie: $data['composition_logement_superficie'] ?? null,
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
