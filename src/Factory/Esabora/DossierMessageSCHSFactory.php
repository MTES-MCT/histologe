<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Messenger\Message\DossierMessageSCHS;
use App\Service\UploadHandlerService;
use App\Utils\AddressParser;
use App\Utils\EtageParser;

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
        $etage = $signalement->getEtageOccupant() ? EtageParser::parse($signalement->getEtageOccupant()) : null;

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
            ->setEtageSignalement($etage)
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
        $commentaire = 'Points signalés:'.\PHP_EOL;

        foreach ($signalement->getCriticites() as $criticite) {
            $commentaire .= \PHP_EOL.$criticite->getCritere()->getLabel().' => Etat '.$criticite->getScoreLabel();
        }

        $commentaire .= \PHP_EOL.'Propriétaire averti: '.$signalement->getIsProprioAverti() ? 'OUI' : 'NON';
        $commentaire .= \PHP_EOL.'Adultes: '.$signalement->getNbAdultes().' Adulte(s)';
        $commentaire .= $this->buildNbEnfants($signalement);

        foreach ($signalement->getAffectations() as $affectation) {
            $commentaire .= \PHP_EOL.$affectation->getPartner()->getNom().' => '.$affectation->getAffectationLabel();
        }

        return $commentaire;
    }

    private function buildNbEnfants(Signalement $signalement)
    {
        $suffix = '';
        if (null !== $signalement->getNbEnfantsM6() && str_ends_with($signalement->getNbEnfantsM6(), '+') ||
            null !== $signalement->getNbEnfantsP6() && str_ends_with($signalement->getNbEnfantsP6(), '+')
        ) {
            $suffix = '+';
        }

        $nbEnfantsM6 = (int) str_replace('+', '', $signalement->getNbEnfantsM6() ?? 0);
        $nbEnfantsP6 = (int) str_replace('+', '', $signalement->getNbEnfantsP6() ?? 0);
        $nbEnfants = $nbEnfantsM6 + $nbEnfantsP6;
        $nbEnfants .= $suffix;

        return \PHP_EOL.$nbEnfants.' Enfant(s)';
    }

    private function buildPiecesJointesObservation(Signalement $signalement): string
    {
        $piecesJointesObservation = '';
        foreach ($signalement->getFiles() as $file) {
            if (!empty($piecesJointesObservation)) {
                $piecesJointesObservation .= ', ';
            }
            $piecesJointesObservation .= $file->getFilename();
        }

        return $piecesJointesObservation;
    }
}
