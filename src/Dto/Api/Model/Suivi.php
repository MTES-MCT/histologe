<?php

namespace App\Dto\Api\Model;

use App\Entity\Suivi as SuiviEntity;

class Suivi
{
    public int $id;
    public string $dateCreation;
    public string $description;
    public bool $public;
    public int $type;

    public function __construct(
        SuiviEntity $suivi,
    ) {
        $this->id = $suivi->getId();
        $this->dateCreation = $suivi->getCreatedAt()->format(\DATE_ATOM);
        $this->description = $suivi->getDescription(); // traitement de suppression du html ? comment gérer les bouton/doc qui sont présent en dur  dans le contenu ?
        $this->public = $suivi->getIsPublic();
        $this->type = $suivi->getType(); // envoyer un libellé ?
        // TODO : exposer "createdBy" attendre merge multi ter. et essayer de faire propre
    }
}
