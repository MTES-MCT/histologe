<?php

namespace App\Dto\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    description: 'Payload édition fichier.',
    required: ['documentType'],
)]
#[Groups(groups: ['Default', 'false'])]
class FileRequest implements RequestInterface
{
    #[OA\Property(
        description: 'Type de document<br>
        <ul>
            <li>`AUTRE_PROCEDURE`</li>
            <li>`AUTRE`</li>
            <li>`SITUATION_FOYER_DPE`</li>
            <li>`SITUATION_FOYER_ETAT_DES_LIEUX`</li>
            <li>`PROCEDURE_RAPPORT_DE_VISITE`</li>
            <li>`SITUATION_FOYER_BAIL`</li>
            <li>`PHOTO_SITUATION`</li>
            <li>`SITUATION_DIAGNOSTIC_PLOMB_AMIANTE`</li>
            <li>`PROCEDURE_MISE_EN_DEMEURE`</li>
            <li>`PROCEDURE_ARRETE_PREFECTORAL`</li>
            <li>`BAILLEUR_REPONSE_BAILLEUR`</li>
            <li>`PROCEDURE_ARRETE_MUNICIPAL`</li>
            <li>`PROCEDURE_SAISINE`</li>
            <li>`BAILLEUR_DEVIS_POUR_TRAVAUX`</li>
        </ul>
        ',
        example: 'BAILLEUR_REPONSE_BAILLEUR'
    )]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: [
            'AUTRE_PROCEDURE',
            'AUTRE',
            'SITUATION_FOYER_DPE',
            'SITUATION_FOYER_ETAT_DES_LIEUX',
            'PROCEDURE_RAPPORT_DE_VISITE',
            'SITUATION_FOYER_BAIL',
            'PHOTO_SITUATION',
            'SITUATION_DIAGNOSTIC_PLOMB_AMIANTE',
            'PROCEDURE_MISE_EN_DEMEURE',
            'PROCEDURE_ARRETE_PREFECTORAL',
            'BAILLEUR_REPONSE_BAILLEUR',
            'PROCEDURE_ARRETE_MUNICIPAL',
            'PROCEDURE_SAISINE',
            'BAILLEUR_DEVIS_POUR_TRAVAUX',
        ],
        message: 'Veuillez choisir une valeur valide pour le type de document. {{ choices }}'
    )]
    public ?string $documentType = null;
    #[OA\Property(
        description: 'La description d\'une photo, elle sera ignoré pour un document',
        example: 'lorem ipsum dolor sit amet'
    )]
    #[Assert\Length(max: 255)]
    public ?string $description = null;
}
