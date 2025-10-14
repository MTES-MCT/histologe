<?php

namespace App\Dto\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    description: 'Payload pour créer une précision libre de désordre.',
    required: ['identifiant', 'description'],
)]
#[Groups(groups: ['Default', 'false'])]
class DesordrePrecisionLibreRequest implements RequestInterface
{
    #[OA\Property(
        description: 'Identifiant du désordre ou de la précision de désordre auquel la description libre est associée.',
        example: 'desordres_batiment_nuisibles_autres',
    )]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [
        'desordres_batiment_nuisibles_autres',
        'desordres_logement_nuisibles_autres',
        'desordres_logement_lumiere_plafond_trop_bas_piece_a_vivre',
        'desordres_logement_lumiere_plafond_trop_bas_cuisine',
        'desordres_logement_lumiere_plafond_trop_bas_salle_de_bain',
        'desordres_logement_lumiere_plafond_trop_bas_toutes_pieces',
    ])]
    public string $identifiant;

    #[OA\Property(
        description: 'Description du désordre ou de la précision.',
        example: 'Invasion de fourmis.',
    )]
    #[Assert\NotBlank]
    public string $description;
}
