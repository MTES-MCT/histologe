<?php

namespace App\Factory\Interconnection\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Repository\SuiviRepository;
use App\Service\HtmlCleaner;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\CiviliteMapper;
use App\Service\Interconnection\Esabora\Enum\PersonneType;
use App\Service\Interconnection\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use App\Utils\AddressParser;
use App\Utils\EscalierParser;
use App\Utils\EtageParser;
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
        return $this->isEsaboraPartnerActive($affectation) && PartnerType::ARS === $affectation->getPartner()->getType();
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function createInstance(Affectation $affectation): DossierMessageSISH
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();
        $timezone = $partner->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;

        $address = AddressParser::parse($signalement->getAdresseOccupant());
        $formatDate = AbstractEsaboraService::FORMAT_DATE;
        $formatDateTime = AbstractEsaboraService::FORMAT_DATE_TIME;
        $routeSignalement = $this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        $etage = $signalement->getEtageOccupant() ? EtageParser::parse($signalement->getEtageOccupant()) : null;
        $escalier = $signalement->getEscalierOccupant()
            ? EscalierParser::parse($signalement->getEscalierOccupant())
            : null;
        $numPorte = $signalement->getNumAppartOccupant()
            ? substr($signalement->getNumAppartOccupant(), 0, 30)
            : null;
        $villeOccupant = $signalement->getVilleOccupant()
            ? substr($signalement->getVilleOccupant(), 0, 60)
            : null;
        $numeroInvariant = null;
        if (!empty($signalement->getNumeroInvariantRial())) {
            $numeroInvariant = substr($signalement->getNumeroInvariantRial(), 0, 12);
        } elseif (!empty($signalement->getNumeroInvariant())) {
            $numeroInvariant = substr($signalement->getNumeroInvariant(), 0, 12);
        }
        $typeEnergieLogement = $signalement->getTypeEnergieLogement()
            ? substr($signalement->getTypeEnergieLogement(), 0, 30)
            : null;

        $codeInsee = $signalement->getInseeOccupant()
            ? substr($signalement->getInseeOccupant(), 0, 5)
            : null;

        return (new DossierMessageSISH())
            ->setUrl($partner->getEsaboraUrl())
            ->setToken($partner->getEsaboraToken())
            ->setPartnerId($partner->getId())
            ->setPartnerType($partner->getType())
            ->setSignalementId($signalement->getId())
            ->setSignalementUrl($this->parameterBag->get('host_url').$routeSignalement)
            ->setReferenceAdresse($signalement->getUuid())
            ->setLocalisationNumero($address['number'] ?? null)
            ->setLocalisationNumeroExt($address['suffix'] ?? null)
            ->setLocalisationAdresse1(
                $address['street']
                ? substr($address['street'], 0, 100)
                    : null
            )
            ->setLocalisationAdresse2($signalement->getAdresseAutreOccupant())
            ->setLocalisationCodePostal($signalement->getCpOccupant())
            ->setLocalisationVille($villeOccupant)
            ->setLocalisationLocalisationInsee($codeInsee)
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($signalement->getUuid())
            ->setSasDateAffectation(
                $affectation
                    ->getCreatedAt()
                    ?->setTimezone(new \DateTimeZone($timezone))
                    ->format($formatDateTime)
            )
            ->setLocalisationEtage(!empty($etage) ? (string) $etage : null)
            ->setLocalisationEscalier($escalier)
            ->setLocalisationNumPorte($numPorte)
            ->setSitOccupantNbAdultes($signalement->getNbAdultes())
            ->setSitOccupantNbEnfantsM6($signalement->getNbEnfantsM6())
            ->setSitOccupantNbEnfantsP6($signalement->getNbEnfantsP6())
            ->setSitOccupantNbOccupants($signalement->getNbOccupantsLogement())
            ->setSitOccupantNumAllocataire($signalement->getNumAllocataire())
            ->setSitOccupantMontantAlloc($signalement->getMontantAllocation())
            ->setSitLogementBailEncours(!empty($signalement->getIsBailEnCours()) ? (int) $signalement->getIsBailEnCours() : null)
            ->setSitLogementBailDateEntree($signalement->getDateEntree()?->format($formatDate))
            ->setSitLogementPreavisDepart((int) $signalement->getIsPreavisDepart())
            ->setSitLogementRelogement((int) $signalement->getIsRelogement())
            ->setSitLogementSuperficie((int) $signalement->getSuperficie())
            ->setSitLogementMontantLoyer($signalement->getLoyer())
            ->setDeclarantNonOccupant(!empty($signalement->getIsNotOccupant()) ? (int) $signalement->getIsNotOccupant() : null)
            ->setLogementNature($signalement->getNatureLogement())
            ->setLogementType($signalement->getNatureLogement())
            ->setLogementSocial(!empty($signalement->getIsLogementSocial()) ? (int) $signalement->getIsLogementSocial() : null)
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
            ->setSignalementScore(round($signalement->getScore(), 1))
            ->setSignalementOrigine(AbstractEsaboraService::SIGNALEMENT_ORIGINE)
            ->setSignalementNumero($signalement->getReference())
            ->setSignalementCommentaire($this->buildSuivi($signalement))
            ->setSignalementDate($signalement->getCreatedAt()?->format($formatDate))
            ->setSignalementDetails($signalement->getDetails())
            ->setSignalementProblemes($this->buildProblemes($signalement))
            ->setPiecesJointesDocuments($this->buildPiecesJointes($signalement))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::OCCUPANT))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::DECLARANT))
            ->addPersonne($this->createDossierPersonne($signalement, PersonneType::PROPRIETAIRE));
    }

    private function createDossierPersonne(
        Signalement $signalement,
        PersonneType $personneType,
    ): ?DossierMessageSISHPersonne {
        if (PersonneType::OCCUPANT === $personneType) {
            $prenom = $signalement->getPrenomOccupant()
                ? substr($signalement->getPrenomOccupant(), 0, 30)
                : null;
            $tel = $signalement->getTelOccupantDecoded(true)
                ? substr($signalement->getTelOccupantDecoded(true), 0, 20)
                : null;
            $personneQualite = CiviliteMapper::mapOccupant($signalement);

            return (new DossierMessageSISHPersonne())
                ->setType(PersonneType::OCCUPANT->value)
                ->setQualite($personneQualite->value)
                ->setNom($signalement->getNomOccupant())
                ->setPrenom($prenom)
                ->setEmail($signalement->getMailOccupant())
                ->setTelephone($tel);
        }

        if (PersonneType::PROPRIETAIRE === $personneType
            && (!empty($signalement->getNomProprio()) || !empty($signalement->getDenominationProprio()))
        ) {
            $tel = $signalement->getTelProprioDecoded(true)
                ? substr($signalement->getTelProprioDecoded(true), 0, 20)
                : null;
            $personneQualite = CiviliteMapper::mapProprio($signalement);
            $prenom = $signalement->getPrenomProprio()
                ? substr($signalement->getPrenomProprio(), 0, 30)
                : null;
            $nom = $signalement->getNomProprio()
                ? substr($signalement->getNomProprio(), 0, 60)
                : null;
            if (empty($nom) && !empty($signalement->getDenominationProprio())) {
                $nom = substr($signalement->getDenominationProprio(), 0, 60);
            }

            $dossierMessageSISHPersonne = new DossierMessageSISHPersonne();
            $dossierMessageSISHPersonne
                ->setType(PersonneType::PROPRIETAIRE->value)
                ->setNom($nom)
                ->setAdresse($signalement->getAdresseProprio())
                ->setEmail($signalement->getMailProprio())
                ->setTelephone($tel);
            if (!empty($personneQualite)) {
                $dossierMessageSISHPersonne->setQualite($personneQualite->value);
            }

            if (!empty($prenom)) {
                $dossierMessageSISHPersonne->setPrenom($prenom);
            }

            return $dossierMessageSISHPersonne;
        }

        if (PersonneType::DECLARANT === $personneType && !empty($signalement->getLienDeclarantOccupant())) {
            $prenom = $signalement->getPrenomDeclarant()
                ? substr($signalement->getPrenomDeclarant(), 0, 30)
                : null;
            $tel = $signalement->getTelDeclarantDecoded(true)
                ? substr($signalement->getTelDeclarantDecoded(true), 0, 20)
                : null;
            $structure = $signalement->getStructureDeclarant()
                ? substr($signalement->getStructureDeclarant(), 0, 150)
                : null;
            $lienOccupant = substr($signalement->getLienDeclarantOccupant(), 0, 150);
            $personneQualite = CiviliteMapper::mapDeclarant($signalement);

            $dossierMessageSISHPersonne = new DossierMessageSISHPersonne();
            $dossierMessageSISHPersonne
                ->setType(PersonneType::DECLARANT->value)
                ->setNom($signalement->getNomDeclarant())
                ->setPrenom($prenom)
                ->setEmail($signalement->getMailDeclarant())
                ->setTelephone($tel)
                ->setStructure($structure)
                ->setLienOccupant($lienOccupant);
            if (!empty($personneQualite)) {
                $dossierMessageSISHPersonne->setQualite($personneQualite->value);
            }

            return $dossierMessageSISHPersonne;
        }

        return null;
    }

    private function buildProblemes(Signalement $signalement): ?string
    {
        $commentaire = null;

        if ($signalement->isV2()) {
            return $this->buildDesordresCreatedFrom($signalement);
        }

        foreach ($signalement->getCriticites() as $criticite) {
            $commentaire .= $criticite->getCritere()->getLabel().' => Etat '.$criticite->getScoreLabel().\PHP_EOL;
        }

        return $commentaire;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function buildSuivi(Signalement $signalement): string
    {
        $suivis = $this->suiviRepository->findAllSuiviBy($signalement, Suivi::TYPE_PARTNER);
        $cleanedSuivis = array_map(function (Suivi $suivi) {
            $mention = 'Par '.$suivi->getCreatedByLabel().', le '.$suivi->getCreatedAt()->format('d/m/Y');
            $cleanSuivi = HtmlCleaner::clean($suivi->getDescription(false));

            return $mention.\PHP_EOL.$cleanSuivi;
        }, $suivis);
        $separator = str_repeat('-', 50);

        return implode(\PHP_EOL.$separator.\PHP_EOL, $cleanedSuivis);
    }
}
