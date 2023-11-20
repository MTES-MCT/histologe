<?php

namespace App\Factory\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Messenger\Message\DossierMessageSISH;
use App\Repository\SuiviRepository;
use App\Service\DataGouv\AddressService;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\Enum\PersonneType;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\HtmlCleaner;
use App\Service\UploadHandlerService;
use App\Utils\AddressParser;
use App\Utils\EscalierParser;
use App\Utils\EtageParser;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DossierMessageSISHFactory extends AbstractDossierMessageFactory
{
    public const DEFAULT_TIMEZONE = 'Europe/Paris';

    public function __construct(
        private readonly SuiviRepository $suiviRepository,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AddressService $addressService,
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

        $cleanedSuiviDescription = null !== $firstSuivi && null !== $firstSuivi->getDescription()
            ? HtmlCleaner::clean($firstSuivi->getDescription())
            : null;

        $formatDate = AbstractEsaboraService::FORMAT_DATE;
        $formatDateTime = AbstractEsaboraService::FORMAT_DATE_TIME;
        $routeSignalement = $this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        $modeContactProprio = \is_array($signalement->getModeContactProprio())
            ? substr(implode(',', $signalement->getModeContactProprio()), 0, 50)
            : null;

        $etage = $signalement->getEtageOccupant() ? EtageParser::parse($signalement->getEtageOccupant()) : null;
        $escalier = $signalement->getEscalierOccupant() ? EscalierParser::parse($signalement->getEscalierOccupant()) : null;
        $numPorte = $signalement->getNumAppartOccupant() ? substr($signalement->getNumAppartOccupant(), 0, 30) : null;
        $villeOccupant = $signalement->getVilleOccupant() ? substr($signalement->getVilleOccupant(), 0, 60) : null;
        $numeroInvariant = $signalement->getNumeroInvariant() ? substr($signalement->getNumeroInvariant(), 0, 12) : null;
        $typeEnergieLogement = $signalement->getTypeEnergieLogement() ? substr($signalement->getTypeEnergieLogement(), 0, 30) : null;
        $codeInsee = $signalement->getInseeOccupant() ?: $this->addressService->getCodeInsee($signalement->getAdresseOccupant().' '.$signalement->getCpOccupant().' '.$signalement->getVilleOccupant());
        $codeInsee = $codeInsee ? substr($codeInsee, 0, 5) : null;

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
            ->setLocalisationAdresse1($address['street'] ? substr($address['street'], 0, 100) : null)
            ->setLocalisationAdresse2($signalement->getAdresseAutreOccupant())
            ->setLocalisationCodePostal($signalement->getCpOccupant())
            ->setLocalisationVille($villeOccupant)
            ->setLocalisationLocalisationInsee($codeInsee)
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($signalement->getUuid())
            ->setSasDateAffectation(
                $affectation
                    ->getCreatedAt()
                    ?->setTimezone(new \DateTimeZone(self::DEFAULT_TIMEZONE))
                    ->format($formatDateTime)
            )
            ->setLocalisationEtage($etage)
            ->setLocalisationEscalier($escalier)
            ->setLocalisationNumPorte($numPorte)
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
            ->setLogementTypeEnergie($typeEnergieLogement)
            ->setLogementCollectif((int) $signalement->getIsLogementCollectif())
            ->setLogementAvant1949((int) $signalement->getIsConstructionAvant1949())
            ->setLogementDiagST((int) $signalement->getIsDiagSocioTechnique())
            ->setLogementInvariant($numeroInvariant)
            ->setLogementNbPieces($signalement->getNbPiecesLogement())
            ->setLogementNbChambres($signalement->getNbChambresLogement())
            ->setLogementNbNiveaux($signalement->getNbNiveauxLogement())
            ->setProprietaireAverti((int) $signalement->getIsProprioAverti())
            ->setProprietaireAvertiDate($signalement->getProprioAvertiAt()?->format($formatDate))
            ->setProprietaireAvertiMoyen($modeContactProprio)
            ->setSignalementScore($signalement->getScore())
            ->setSignalementOrigine(AbstractEsaboraService::SIGNALEMENT_ORIGINE)
            ->setSignalementNumero($signalement->getReference())
            ->setSignalementCommentaire($cleanedSuiviDescription)
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
            $prenom = $signalement->getPrenomOccupant() ? substr($signalement->getPrenomOccupant(), 0, 30) : null;
            $tel = $signalement->getTelOccupant() ? substr($signalement->getTelOccupant(), 0, 20) : null;

            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::OCCUPANT->value)
                ->setNom($signalement->getNomOccupant())
                ->setPrenom($prenom)
                ->setEmail($signalement->getMailOccupant())
                ->setTelephone($tel);
        }

        if (PersonneType::PROPRIETAIRE === $personneType && !empty($signalement->getNomProprio())) {
            $tel = $signalement->getTelProprio() ? substr($signalement->getTelProprio(), 0, 20) : null;

            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::PROPRIETAIRE->value)
                ->setNom(substr($signalement->getNomProprio(), 0, 60))
                ->setAdresse($signalement->getAdresseProprio())
                ->setEmail($signalement->getMailProprio())
                ->setTelephone($tel);
        }

        if (PersonneType::DECLARANT === $personneType && !empty($signalement->getLienDeclarantOccupant())) {
            $prenom = $signalement->getPrenomDeclarant() ? substr($signalement->getPrenomDeclarant(), 0, 30) : null;
            $tel = $signalement->getTelDeclarant() ? substr($signalement->getTelDeclarant(), 0, 20) : null;
            $structure = $signalement->getStructureDeclarant() ? substr($signalement->getStructureDeclarant(), 0, 150) : null;
            $lienOccupant = $signalement->getLienDeclarantOccupant() ? substr($signalement->getLienDeclarantOccupant(), 0, 150) : null;

            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::DECLARANT->value)
                ->setNom($signalement->getNomDeclarant())
                ->setPrenom($prenom)
                ->setEmail($signalement->getMailDeclarant())
                ->setTelephone($tel)
                ->setStructure($structure)
                ->setLienOccupant($lienOccupant);
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
