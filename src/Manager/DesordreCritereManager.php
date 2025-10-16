<?php

namespace App\Manager;

use App\Entity\DesordreCritere;
use Doctrine\Persistence\ManagerRegistry;

class DesordreCritereManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry, string $entityName = DesordreCritere::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param array<string, mixed> $filteredDataOfDraft
     * @param array<int, mixed>    $availableCritereSlugs
     *
     * @return array<string, mixed>
     */
    public function getCriteresSlugsInDraft(array $filteredDataOfDraft, array $availableCritereSlugs): array
    {
        $criteresSlugs = array_filter($filteredDataOfDraft, function ($value, $slug) use ($availableCritereSlugs) {
            if (\in_array($slug, $availableCritereSlugs)) {
                if (1 === $value) {
                    return true;
                }
            }

            return false;
        }, \ARRAY_FILTER_USE_BOTH);

        // cas particulier pour desordres_logement_chauffage_type_aucun
        if (isset($filteredDataOfDraft['desordres_logement_chauffage_type'])
            && 'aucun' === $filteredDataOfDraft['desordres_logement_chauffage_type']) {
            $criteresSlugs['desordres_logement_chauffage_type_aucun'] = 1;
        }

        // cas particulier pour desordres_logement_proprete
        if (
            (
                isset($filteredDataOfDraft['desordres_logement_proprete_pieces_piece_a_vivre'])
                && 1 === $filteredDataOfDraft['desordres_logement_proprete_pieces_piece_a_vivre']
            )
            || (
                isset($filteredDataOfDraft['desordres_logement_proprete_pieces_cuisine'])
                && 1 === $filteredDataOfDraft['desordres_logement_proprete_pieces_cuisine']
            )
            || (
                isset($filteredDataOfDraft['desordres_logement_proprete_pieces_salle_de_bain'])
                && 1 === $filteredDataOfDraft['desordres_logement_proprete_pieces_salle_de_bain']
            )
        ) {
            $criteresSlugs['desordres_logement_proprete'] = 1;
        }

        return $criteresSlugs;
    }
}
