<?php

namespace App\Dto\Api\Model;

use App\Entity\Criticite;
use App\Entity\DesordrePrecision;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Desordre',
    description: 'Schéma représentant les désordres identifiés dans le logement.'
)]
class Desordre
{
    #[OA\Property(
        description: 'Identifiant du désordre.',
        example: 'desordres_logement_humidite_salle_de_bain'
    )]
    public ?string $identifiant = null;
    #[OA\Property(
        description: 'Libellé du désordre.',
        example: 'Le logement est humide et a des traces de moisissures'
    )]
    public string $libelle;
    /** @var array<string, string> */
    #[OA\Property(
        description: 'Tableau d\'identifiants des précisions du désordre.',
        example: [
            'desordres_logement_humidite_salle_de_bain_details_machine_non' => 'Dans : La salle de bain, salle d\'eau et / ou les toilettes - Machine à laver, sèche-linge ou lave vaisselle : non',
            'desordres_logement_humidite_salle_de_bain_details_moisissure_apres_nettoyage_oui' => 'Dans : La salle de bain, salle d\'eau et / ou les toilettes - Moisissure après nettoyage : oui',
        ]
    )]
    public array $precisions = [];
    /** @var array<int, array{identifiant: string, description: string}> */
    #[OA\Property(
        description: 'Tableau des précisions libre du désordre.',
        example: [
            'identifiant' => 'desordres_batiment_nuisibles_autres',
            'description' => 'Invasion de fourmis.',
        ]
    )]
    public array $precisionsLibres = [];

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $precisionsLibres
     */
    #[OA\Property(
        description: 'Catégorie du désordre.',
        example: 'Eau potable / assainissement'
    )]
    public function __construct(
        array $data,
        array $precisionsLibres = [],
    ) {
        foreach ($data as $label => $detail) {
            if ($detail instanceof Criticite && $detail->getLabel()) {
                $this->identifiant = '__criticite_historique__';
                $this->libelle = $label;
                $this->precisions['__criticite_historique__'] = $detail->getLabel();
            } else {
                foreach ($detail as $desordrePrecision) {
                    if ($desordrePrecision instanceof DesordrePrecision) {
                        $this->libelle = $desordrePrecision->getDesordreCritere()->getLabelCritere();
                        if ($desordrePrecision->getDesordreCritere()->getDesordrePrecisions()->count() > 1) {
                            $this->identifiant = $desordrePrecision->getDesordreCritere()->getSlugCritere();
                            $labelCleaned = strip_tags(str_replace('<br>', ' - ', $desordrePrecision->getLabel()));
                            $this->precisions[$desordrePrecision->getDesordrePrecisionSlug()] = $labelCleaned;
                            if (!empty($precisionsLibres[$desordrePrecision->getDesordrePrecisionSlug()])) {
                                $this->precisionsLibres[] = [
                                    'identifiant' => $desordrePrecision->getDesordrePrecisionSlug(),
                                    'description' => $precisionsLibres[$desordrePrecision->getDesordrePrecisionSlug()],
                                ];
                            }
                        } else {
                            $this->identifiant = $desordrePrecision->getDesordrePrecisionSlug();
                            if (!empty($precisionsLibres[$this->identifiant])) {
                                $this->precisionsLibres[] = [
                                    'identifiant' => $this->identifiant,
                                    'description' => $precisionsLibres[$this->identifiant],
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
}
