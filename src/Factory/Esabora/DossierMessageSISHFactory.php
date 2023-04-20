<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\AddressParser;

class DossierMessageSISHFactory
{
    public function __construct(private readonly AddressParser $addressParser)
    {
    }

    public function createInstance(Affectation $affectation): DossierMessageSISH
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        list($numero, $extension, $adresse) = $this->addressParser->parse($signalement->getAdresseOccupant());
        return (new DossierMessageSISH())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setSignalementId($signalement->getId())
            ->setReferenceAdresse($signalement->getUuid())
            ->setLocalisationNumero($numero)
            ->setLocalisationNumeroExt($extension)
            ->setLocalisationAdresse1($adresse)
            ->setLocalisationAdresse2($signalement->getAdresseAutreOccupant())
            ->setLocalisationCodePostal($signalement->getCpOccupant())
            ->setLocalisationVille($signalement->getVilleOccupant())
            ->setLocalisationLocalisationInsee($signalement->getInseeOccupant());

    }
}