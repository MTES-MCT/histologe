<?php

namespace App\Dto\Api\Response;

use App\Entity\Suivi;

class SuiviResponse
{
    public int $id;
    public string $dateCreation;
    public string $description;
    public bool $public;
    public int $type;

    public function __construct(
        Suivi $suivi,
    ) {
        $this->id = $suivi->getId();
        $this->dateCreation = $suivi->getCreatedAt()->format(\DATE_ATOM);
        $this->description = $suivi->getDescription(); // traitement de suppression du html ? comment gérer les bouton/doc qui sont présent en dur  dans le contenu ?
        $this->public = $suivi->getIsPublic();
        $this->type = $suivi->getType(); // envoyer un libellé ?
        // exposer "createdBy" sous quelle forme ?
    }
}
