<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Messenger\Message\DossierMessageSISH;
use App\Repository\SuiviRepository;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\Enum\PersonneType;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\UploadHandlerService;
use App\Utils\AddressParser;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DossierMessageSISHFactory extends AbstractDossierMessageFactory
{
    public function __construct(
        private readonly SuiviRepository $suiviRepository,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($this->uploadHandlerService);
    }

    public function supports(Affectation $affectation): bool
    {
        return PartnerType::ARS === $affectation->getPartner()->getType();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function createInstance(Affectation $affectation): DossierMessageSISH
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();

        $address = AddressParser::parse($signalement->getAdresseOccupant());
        $firstSuivi = $this->suiviRepository->findFirstSuiviBy($signalement, Suivi::TYPE_PARTNER);
        $formatDate = AbstractEsaboraService::FORMAT_DATE;
        $formatDateTime = AbstractEsaboraService::FORMAT_DATE_TIME;
        $routeSignalement = $this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        return (new DossierMessageSISH())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setPartnerType($partner->getType()->value)
            ->setSignalementId($signalement->getId())
            ->setSignalementUrl($this->parameterBag->get('host_url').$routeSignalement)
            ->setReferenceAdresse($signalement->getUuid())
            ->setLocalisationNumero($address['number'] ?? null)
            ->setLocalisationNumeroExt($address['suffix'] ?? null)
            ->setLocalisationAdresse1($address['street'] ?? null)
            ->setLocalisationAdresse2($signalement->getAdresseAutreOccupant())
            ->setLocalisationCodePostal($signalement->getCpOccupant())
            ->setLocalisationVille($signalement->getVilleOccupant())
            ->setLocalisationLocalisationInsee($signalement->getInseeOccupant())
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($signalement->getUuid())
            ->setSasDateAffectation($affectation->getCreatedAt()?->format($formatDateTime))
            ->setLocalisationEtage($signalement->getEtageOccupant())
            ->setLocalisationEscalier($signalement->getEscalierOccupant())
            ->setLocalisationNumPorte($signalement->getNumAppartOccupant())
            ->setSitOccupantNbAdultes($signalement->getNbAdultes())
            ->setSitOccupantNbEnfantsM6($signalement->getNbEnfantsM6())
            ->setSitOccupantNbEnfantsP6($signalement->getNbEnfantsP6())
            ->setSitOccupantNbOccupants($signalement->getNbOccupantsLogement())
            ->setSitOccupantNumAllocataire($signalement->getNumAllocataire())
            ->setSitOccupantMontantAlloc($signalement->getMontantAllocation())
            ->setSitLogementBailEncours((int) $signalement->getIsBailEnCours())
            ->setSitLogementBailDateEntree($signalement->getDateEntree()?->format($formatDate))
            ->setSitLogementPreavisDepart((int) $signalement->getIsPreavisDepart())
            ->setSitLogementRelogement((int) $signalement->getIsRelogement())
            ->setSitLogementSuperficie($signalement->getSuperficie())
            ->setSitLogementMontantLoyer($signalement->getLoyer())
            ->setDeclarantNonOccupant($signalement->getIsNotOccupant())
            ->setLogementNature($signalement->getNatureLogement())
            ->setLogementType($signalement->getTypeLogement())
            ->setLogementSocial($signalement->getIsLogementSocial())
            ->setLogementAnneeConstruction($signalement->getAnneeConstruction())
            ->setLogementTypeEnergie($signalement->getTypeEnergieLogement())
            ->setLogementCollectif((int) $signalement->getIsLogementCollectif())
            ->setLogementAvant1949((int) $signalement->getIsConstructionAvant1949())
            ->setLogementDiagST((int) $signalement->getIsDiagSocioTechnique())
            ->setLogementInvariant($signalement->getNumeroInvariant())
            ->setLogementNbPieces($signalement->getNbPiecesLogement())
            ->setLogementNbChambres($signalement->getNbChambresLogement())
            ->setLogementNbNiveaux($signalement->getNbNiveauxLogement())
            ->setProprietaireAverti((int) $signalement->getIsProprioAverti())
            ->setProprietaireAvertiDate($signalement->getProprioAvertiAt()?->format($formatDate))
            ->setProprietaireAvertiMoyen(implode(',', $signalement->getModeContactProprio()))
            ->setSignalementScore($signalement->getScore())
            ->setSignalementOrigine(AbstractEsaboraService::SIGNALEMENT_ORIGINE)
            ->setSignalementNumero($signalement->getReference())
            ->setSignalementCommentaire($firstSuivi?->getDescription())
            ->setSignalementDate($signalement->getCreatedAt()?->format($formatDate))
            ->setSignalementDetails($signalement->getDetails())
            ->setSignalementProblemes($this->buildProblemes($signalement))
            ->setPiecesJointesDocuments($this->buildPiecesJointes($signalement))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::OCCUPANT))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::DECLARANT))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::PROPRIETAIRE));
    }

    public function createDossierPersonne(
        Signalement $signalement,
        PersonneType $personneType
    ): ?DossierMessageSISHPersonne {
        if (PersonneType::OCCUPANT === $personneType) {
            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::OCCUPANT->value)
                ->setNom($signalement->getNomOccupant())
                ->setPrenom($signalement->getPrenomOccupant())
                ->setEmail($signalement->getMailOccupant())
                ->setTelephone($signalement->getTelOccupant());
        }

        if (PersonneType::PROPRIETAIRE === $personneType && !empty($signalement->getNomProprio())) {
            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::PROPRIETAIRE->value)
                ->setNom($signalement->getNomProprio())
                ->setAdresse($signalement->getAdresseProprio())
                ->setEmail($signalement->getMailProprio())
                ->setTelephone($signalement->getTelProprio());
        }

        if (PersonneType::DECLARANT === $personneType && !empty($signalement->getLienDeclarantOccupant())) {
            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::DECLARANT->value)
                ->setNom($signalement->getNomDeclarant())
                ->setPrenom($signalement->getPrenomDeclarant())
                ->setEmail($signalement->getMailDeclarant())
                ->setTelephone($signalement->getTelDeclarant())
                ->setStructure($signalement->getStructureDeclarant())
                ->setLienOccupant($signalement->getLienDeclarantOccupant());
        }

        return null;
    }

    private function buildProblemes(Signalement $signalement): ?string
    {
        $commentaire = null;
        foreach ($signalement->getCriticites() as $criticite) {
            $commentaire .= $criticite->getCritere()->getLabel().' => Etat '.$criticite->getScoreLabel().\PHP_EOL;
        }

        return $commentaire;
    }
}
