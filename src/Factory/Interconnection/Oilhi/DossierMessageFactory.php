<?php

namespace App\Factory\Interconnection\Oilhi;

use App\Entity\Affectation;
use App\Entity\Criticite;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Factory\Interconnection\DossierMessageFactoryInterface;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Service\HtmlCleaner;
use App\Service\Interconnection\Oilhi\HookZapierService;
use App\Service\Interconnection\Oilhi\Model\Desordre;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DossierMessageFactory implements DossierMessageFactoryInterface
{
    public const string FORMAT_DATE = 'Y-m-d';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'FEATURE_OILHI_ENABLE')]
        private readonly bool $featureEnable,
    ) {
    }

    public function supports(Affectation $affectation): bool
    {
        $partner = $affectation->getPartner();
        $signalement = $affectation->getSignalement();

        return $this->featureEnable
        && $signalement->hasQualificaton(Qualification::RSD)
        && $partner->canSyncWithOilhi($signalement);
    }

    public function createInstance(Affectation $affectation): DossierMessage
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();
        $interventionData = $this->buildInterventionData($signalement);
        $typeDeclarant = $this->getTypeDeclarant($signalement);

        return (new DossierMessage())
            ->setAction(HookZapierService::ACTION_PUSH_DOSSIER)
            ->setUuidSignalement($uuid = $signalement->getUuid())
            ->setPartnerId($partner->getId())
            ->setPartnerType($partner->getType())
            ->setSignalementId($signalement->getId())
            ->setSignalementUrl($this->urlGenerator->generate(
                'back_signalement_view',
                ['uuid' => $uuid],
                UrlGeneratorInterface::ABSOLUTE_URL
            ))
            ->setDateDepotSignalement(
                null !== $signalement->getValidatedAt()
                    ? $signalement->getValidatedAt()->format(self::FORMAT_DATE)
                    : $signalement->getCreatedAt()->format(self::FORMAT_DATE)
            )
            ->setDateAffectationSignalement($affectation->getCreatedAt()->format(self::FORMAT_DATE))
            ->setCourrielPartenaire($partner->getEmail())
            ->setCourrielContributeurs(implode(',', $partner->getEmailActiveUsers()))
            ->setAdresseSignalement($signalement->getAdresseOccupant())
            ->setCommuneSignalement($signalement->getVilleOccupant())
            ->setCodePostalSignalement($signalement->getCpOccupant())
            ->setTypeDeclarant($typeDeclarant)
            ->setTelephoneDeclarant(
                $signalement->getIsNotOccupant()
                    ? $signalement->getTelDeclarant()
                    : $signalement->getTelOccupant()
            )
            ->setCourrielDeclarant(
                $signalement->getIsNotOccupant()
                    ? $signalement->getMailDeclarant()
                    : $signalement->getMailOccupant()
            )
            ->setNomOccupant($signalement->getNomOccupant())
            ->setPrenomOccupant($signalement->getPrenomOccupant())
            ->setTelephoneOccupant($signalement->getTelOccupant())
            ->setCourrielOccupant($signalement->getMailOccupant())
            ->setNomProprietaire($signalement->getNomProprio().' '.$signalement->getPrenomProprio())
            ->setTelephoneProprietaire($signalement->getTelProprio())
            ->setCourrielProprietaire($signalement->getMailProprio())
            ->setRapportVisite($interventionData['rapport_visite'] ?? null)
            ->setDateVisite($interventionData['date_visite'] ?? null)
            ->setOperateurVisite($interventionData['operateur_visite'] ?? null)
            ->setDesordres($this->buildDesordres($signalement))
            ->setNbOccupants($signalement->getNbOccupantsLogement());
    }

    /**
     * @return array Un tableau associatif contenant les informations de l'intervention.
     *               - 'date_visite': La date de la visite au format 'Y-m-d'.
     *               - 'operateur_visite': Le nom du partenaire opérateur de la visite.
     *               - 'rapport_visite': L'URL du rapport de visite avec le jeton CSRF.
     */
    private function buildInterventionData(Signalement $signalement): ?array
    {
        /** @var Intervention|false $intervention */
        $intervention = $signalement->getInterventions()->last();
        if (false === $intervention) {
            return null;
        }

        $interventionData['date_visite'] = $intervention->getScheduledAt()->format(self::FORMAT_DATE);
        $interventionData['operateur_visite'] = $intervention->getDoneBy() ?? $intervention->getPartner()->getNom();
        $interventionData['rapport_visite'] = $this->getRapportVisite($intervention);

        return $interventionData;
    }

    /**
     * @return array|Desordre[]
     */
    private function buildDesordres(Signalement $signalement): array
    {
        /** @var Desordre[] $desordres */
        $desordres = [];
        if ($signalement->getCreatedFrom()) {
            foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
                $desordre = new Desordre(
                    desordre: $desordrePrecision->getDesordreCritere()->getDesordreCategorie()->getLabel(),
                    equipement: $desordrePrecision->getDesordreCritere()->getLabelCritere(),
                    risque: HtmlCleaner::clean($desordrePrecision->getLabel()),
                    isDanger: $desordrePrecision->getIsDanger(),
                    isSurrocupation: $desordrePrecision->getIsSuroccupation(),
                    isInsalubrite: $desordrePrecision->getIsInsalubrite()
                );
                $desordres[] = $desordre;
            }

            return $desordres;
        }

        $criticites = $signalement->getCriticites();
        /** @var Criticite $criticite */
        foreach ($criticites as $criticite) {
            $desordre = new Desordre(
                desordre: $criticite->getCritere()->getSituation()->getLabel(),
                equipement: $criticite->getCritere()->getLabel(),
                risque: $criticite->getLabel(),
                isDanger: $criticite->getIsDanger(),
            );
            $desordres[] = $desordre;
        }

        return $desordres;
    }

    private function getTypeDeclarant(Signalement $signalement): ?string
    {
        $typeDeclarant = null;
        if ($signalement->getCreatedFrom()) {
            $typeDeclarant = $signalement->getProfileDeclarant()->label();
        }

        if (!$signalement->getIsNotOccupant()) {
            $typeDeclarant = $signalement->getLienDeclarantOccupant()
            ? strtoupper($signalement->getLienDeclarantOccupant())
            : OccupantLink::AUTRE->label();
        }

        return $typeDeclarant;
    }

    private function getRapportVisite(Intervention $intervention): ?string
    {
        if ($intervention->getFiles()->isEmpty()) {
            return null;
        }

        return $this->urlGenerator->generate(
            'show_file',
            ['uuid' => $intervention->getFiles()->first()->getUuid()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
