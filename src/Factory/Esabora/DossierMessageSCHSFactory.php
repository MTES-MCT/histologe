<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Messenger\Message\DossierMessageSCHS;
use App\Service\UploadHandlerService;

class DossierMessageSCHSFactory
{
    public function __construct(private readonly UploadHandlerService $uploadHandlerService)
    {
    }

    public function createInstance(Affectation $affectation): DossierMessageSCHS
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

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
            ->setAdresseSignalement($this->getAdresseWithoutNumero($signalement->getAdresseOccupant()))
            ->setCodepostaleSignalement($signalement->getCpOccupant())
            ->setVilleSignalement($signalement->getVilleOccupant())
            ->setEtageSignalement($signalement->getEtageOccupant())
            ->setNumeroAppartementSignalement($signalement->getNumAppartOccupant())
            ->setNumeroAdresseSignalement($this->getNumeroAdresse($signalement->getAdresseOccupant()))
            ->setLatitudeSignalement($signalement->getGeoloc()['lat'] ?? 0)
            ->setLongitudeSignalement($signalement->getGeoloc()['lng'] ?? 0)
            ->setDateOuverture($signalement->getCreatedAt()->format('d/m/Y'))
            ->setDossierCommentaire($this->buildCommentaire($signalement))
            ->setPiecesJointesObservation($this->buildPiecesJointesObservation($signalement))
            ->setPiecesJointes($this->buildPiecesJointes($signalement));
    }

    private function getNumeroAdresse(string $adresse): string
    {
        preg_match('!\d+!', $adresse, $matches);

        return $matches[0] ?? '';
    }

    private function getAdresseWithoutNumero(string $adresse): string
    {
        $numero = $this->getNumeroAdresse($adresse);
        $adresse = trim(substr($adresse, \strlen($numero)));

        return $adresse;
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
        foreach ($signalement->getDocuments() as $document) {
            $piecesJointesObservation .= $document['titre'].', ';
        }

        return $piecesJointesObservation;
    }

    private function buildPiecesJointes(Signalement $signalement): array
    {
        $piecesJointes = [];
        foreach ($signalement->getDocuments() as $document) {
            $filepath = $this->uploadHandlerService->getTmpFilepath($document['file']);
            $piecesJointes[] = [
                'documentName' => $document['titre'],
                'documentSize' => filesize($filepath),
                'documentContent' => $document['file'],
            ];
        }
        foreach ($signalement->getPhotos() as $photo) {
            $filepath = $this->uploadHandlerService->getTmpFilepath($photo['file']);
            $piecesJointes[] = [
                'documentName' => 'Image téléversée',
                'documentSize' => filesize($filepath),
                'documentContent' => $photo['file'],
            ];
        }

        return $piecesJointes;
    }
}
