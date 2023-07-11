<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Messenger\Message\DossierMessageSCHS;
use App\Service\UploadHandlerService;
use App\Utils\AddressParser;

class DossierMessageSCHSFactory extends AbstractDossierMessageFactory
{
    public function __construct(
        private readonly UploadHandlerService $uploadHandlerService
    ) {
        parent::__construct($this->uploadHandlerService);
    }

    public function supports(Affectation $affectation): bool
    {
        return PartnerType::COMMUNE_SCHS === $affectation->getPartner()->getType();
    }

    public function createInstance(Affectation $affectation): DossierMessageSCHS
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();
        $address = AddressParser::parse($signalement->getAdresseOccupant());

        return (new DossierMessageSCHS())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setSignalementId($signalement->getId())
            ->setReference($signalement->getUuid())
            ->setNomUsager($signalement->getNomOccupant())
            ->setPrenomUsager($signalement->getPrenomOccupant())
            ->setMailUsager($signalement->getMailOccupant())
            ->setTelephoneUsager($signalement->getTelOccupant())
            ->setAdresseSignalement($address['street'])
            ->setCodepostaleSignalement($signalement->getCpOccupant())
            ->setVilleSignalement($signalement->getVilleOccupant())
            ->setEtageSignalement($signalement->getEtageOccupant())
            ->setNumeroAppartementSignalement($signalement->getNumAppartOccupant())
            ->setNumeroAdresseSignalement($address['number'])
            ->setLatitudeSignalement($signalement->getGeoloc()['lat'] ?? 0)
            ->setLongitudeSignalement($signalement->getGeoloc()['lng'] ?? 0)
            ->setDateOuverture($signalement->getCreatedAt()->format('d/m/Y'))
            ->setDossierCommentaire($this->buildCommentaire($signalement))
            ->setPiecesJointesObservation($this->buildPiecesJointesObservation($signalement))
            ->setPiecesJointes($this->buildPiecesJointes($signalement));
    }

    private function buildCommentaire(Signalement $signalement): string
    {
        $commentaire = 'Points signalés:\n';

        foreach ($signalement->getCriticites() as $criticite) {
            $commentaire .= '\n'.$criticite->getCritere()->getLabel().' => Etat '.$criticite->getScoreLabel();
        }

        $commentaire .= '\nPropriétaire averti: '.$signalement->getIsProprioAverti() ? 'OUI' : 'NON';
        $commentaire .= '\nAdultes: '.$signalement->getNbAdultes().' Adultes';
        $commentaire .= '\n'.$signalement->getNbEnfantsM6() + $signalement->getNbEnfantsP6().' Enfants';

        foreach ($signalement->getAffectations() as $affectation) {
            $commentaire .= '\n'.$affectation->getPartner()->getNom().' => '.$affectation->getAffectationLabel();
        }

        return $commentaire;
    }

    private function buildPiecesJointesObservation(Signalement $signalement): string
    {
        $piecesJointesObservation = '';
        foreach ($signalement->getFiles() as $file) {
            $piecesJointesObservation .= $file->getTitle().', ';
        }

        return $piecesJointesObservation;
    }
}
