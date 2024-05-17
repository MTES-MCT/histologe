<?php

namespace App\Factory\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
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
        if (Affectation::STATUS_WAIT !== $affectation->getStatut()) {
            return false;
        }
        if (!$this->isEsaboraPartnerActive($affectation)) {
            return false;
        }
        if (PartnerType::COMMUNE_SCHS !== $affectation->getPartner()->getType()) {
            return false;
        }

        return true;
    }

    public function createInstance(Affectation $affectation): DossierMessageSCHS
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();
        $address = AddressParser::parse($signalement->getAdresseOccupant());
        $etage = $signalement->getEtageOccupant() ? EtageParser::parse($signalement->getEtageOccupant()) : null;
        $numeroAppartement = !empty($signalement->getNumAppartOccupant())
            ? substr($signalement->getNumAppartOccupant(), 0, 5)
            : null;
        $nomUsager = !empty($signalement->getNomOccupant()) ? $signalement->getNomOccupant() : null;
        $prenomUsager = !empty($signalement->getPrenomOccupant())
            ? substr($signalement->getPrenomOccupant(), 0, 25)
            : null;

        return (new DossierMessageSCHS())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setSignalementId($signalement->getId())
            ->setReference($signalement->getUuid())
            ->setNomUsager($nomUsager)
            ->setPrenomUsager($prenomUsager)
            ->setMailUsager($signalement->getMailOccupant())
            ->setTelephoneUsager($signalement->getTelOccupantDecoded(true))
            ->setAdresseSignalement($address['street'])
            ->setCodepostaleSignalement($signalement->getCpOccupant())
            ->setVilleSignalement($signalement->getVilleOccupant())
            ->setEtageSignalement($etage)
            ->setNumeroAppartementSignalement($numeroAppartement)
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

        if ($signalement->getCreatedFrom()) {
            $commentaire .= $this->buildDesordresCreatedFrom($signalement);
        } else {
            foreach ($signalement->getCriticites() as $criticite) {
                $commentaire .= \PHP_EOL.$criticite->getCritere()->getLabel().' => Etat '.$criticite->getScoreLabel();
            }
        }

        $commentaire .= \PHP_EOL.'Propriétaire averti : ';
        $commentaire .= $signalement->getIsProprioAverti() ? 'OUI' : 'NON';

        if ($signalement->getCreatedFrom()) {
            $commentaire .= \PHP_EOL.'Nb personnes : '.$signalement->getTypeCompositionLogement()->getCompositionLogementNombrePersonnes();
            $commentaire .= \PHP_EOL.'Enfants moins de 6 ans : '.$signalement->getTypeCompositionLogement()->getCompositionLogementEnfants();
        } else {
            $commentaire .= \PHP_EOL.'Adultes : '.$signalement->getNbAdultes().' Adulte(s)';
            $commentaire .= $this->buildNbEnfants($signalement);
        }

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
