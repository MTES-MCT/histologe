<?php

namespace App\Dto\Api\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'File',
    description: 'Représentation d\'un fichier.'
)]
class File
{
    #[OA\Property(
        description: 'Uuid du fichier',
        example: '123e4567-e89b-12d3-a456-426614174000',
    )]
    public string $uuid;

    #[OA\Property(
        description: 'Titre du fichier',
        example: 'sample.png',
    )]
    public string $titre;
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
            <li>`PHOTO_VISITE`</li>
            <li>`SITUATION_DIAGNOSTIC_PLOMB_AMIANTE`</li>
            <li>`PROCEDURE_MISE_EN_DEMEURE`</li>
            <li>`PROCEDURE_ARRETE_PREFECTORAL`</li>
            <li>`BAILLEUR_REPONSE_BAILLEUR`</li>
            <li>`PROCEDURE_ARRETE_MUNICIPAL`</li>
            <li>`PROCEDURE_SAISINE`</li>
            <li>`BAILLEUR_DEVIS_POUR_TRAVAUX`</li>
            <li>`EXPORT`</li>
        </ul>
        ',
        example: 'PHOTO_VISITE'
    )]
    public string $documentType;
    #[OA\Property(
        description: 'Description du fichier (uniquement pour les documents de type images)',
        example: 'Dégats dans la cuisine'
    )]
    public ?string $description;
    #[OA\Property(
        description: 'URL du fichier<br>
        Le nom du fichier peut être récupéré avec les informations du header HTTP de la ressource `Content-Disposition` indiquant son nom et son extension.
        <br> Exemple : `Content-Disposition: inline; filename=sample_demo_resize.png`',
        example: 'https://histologe-staging.osc-fr1.scalingo.io/show/5ca99705-5ef6-11ef-ba0f-0242ac110034'
    )]
    public string $url;
}
