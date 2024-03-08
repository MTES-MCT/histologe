<?php

namespace App\Factory\Interconnection\Oilhi;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\DesordreCategorie;
use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Factory\Interconnection\DossierMessageFactoryInterface;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Service\HtmlCleaner;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DossierMessageFactory implements DossierMessageFactoryInterface
{
    public const FORMAT_DATE = 'Y-m-d';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire(env: 'FEATURE_OILHI_ENABLE')]
        private bool $featureEnable,
    ) {
    }

    public function supports(Affectation $affectation): bool
    {
        $partner = $affectation->getPartner();
        $signalement = $affectation->getSignalement();

        return $this->featureEnable
            && $signalement->hasQualificaton(Qualification::RSD)
            && $partner->canSyncWithOilhi();
    }

    public function createInstance(Affectation $affectation): DossierMessage
    {
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();
        $interventionData = $this->buildInterventionData($signalement);
        $desordresData = $this->buildDesordresData($signalement);
        $typeDeclarant = $this->getTypeDeclarant($signalement);

        return (new DossierMessage())
            ->setUuidSignalement($uuid = $signalement->getUuid())
            ->setPartnerId($partner->getId())
            ->setSignalementId($signalement->getId())
            ->setSignalementUrl($this->urlGenerator->generate(
                'back_signalement_view',
                ['uuid' => $uuid],
                UrlGeneratorInterface::ABSOLUTE_URL))
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
                    : $signalement->getTelOccupant())
            ->setCourrielDeclarant(
                $signalement->getIsNotOccupant()
                    ? $signalement->getMailDeclarant()
                    : $signalement->getMailOccupant())
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
            ->setDesordresCategorie($desordresData['categories'] ?? null)
            ->setDesordresCritere($desordresData['criteres'] ?? null)
            ->setDesordresPrecision($desordresData['precisions'] ?? null);
    }

    /**
     * @return array Un tableau associatif contenant les informations de l'intervention.
     *               - 'date_visite': La date de la visite au format 'Y-m-d'.
     *               - 'operateur_visite': Le nom du partenaire opérateur de la visite.
     *               - 'rapport_visite': L'URL du rapport de visite avec le jeton CSRF.
     */
    private function buildInterventionData(Signalement $signalement): ?array
    {
        /** @var Intervention $intervention */
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
     * @return array Un tableau associatif contenant les libéllés des désordres pour ancien et nouveau formulaire
     *               - 'categories': Les catégories de désordres/les situations.
     *               - 'criteres': Les critères de désordres.
     *               - 'precisions': Les précisions sur les désordres/les criticités.
     */
    private function buildDesordresData(Signalement $signalement): array
    {
        $desordres = [];
        if ($signalement->getCreatedFrom()) {
            if (!$signalement->getDesordreCategories()->isEmpty()) {
                $desordres['categories'] = implode(
                    ',',
                    $signalement
                        ->getDesordreCategories()
                        ->map(function (DesordreCategorie $desordreCategorie) {
                            return $desordreCategorie->getLabel();
                        })->toArray()
                );
            }

            if (!$signalement->getDesordreCriteres()->isEmpty()) {
                $desordres['criteres'] = implode(
                    ',',
                    $signalement
                        ->getDesordreCriteres()
                        ->map(function (DesordreCritere $desordreCritere) {
                            return $desordreCritere->getLabelCritere();
                        })->toArray()
                );
            }

            if (!$signalement->getDesordrePrecisions()->isEmpty()) {
                $precisionsList = $signalement
                    ->getDesordrePrecisions()
                    ->filter(function (DesordrePrecision $desordrePrecision) {
                        return ' - ' !== $desordrePrecision->getLabel();
                    })
                    ->map(function (DesordrePrecision $desordrePrecision) {
                        if (!empty($desordrePrecision->getLabel())) {
                            return HtmlCleaner::clean($desordrePrecision->getLabel());
                        }
                    })->toArray();
                $desordres['precisions'] = implode(',', array_filter($precisionsList));
            }

            return $desordres;
        }

        if (!$signalement->getSituations()->isEmpty()) {
            $desordres['categories'] = implode(
                ',',
                $signalement
                    ->getSituations()
                    ->map(function (Situation $situation) {
                        return $situation->getLabel();
                    })->toArray()
            );
        }

        if (!$signalement->getCriteres()->isEmpty()) {
            $desordres['criteres'] = implode(
                ',',
                $signalement
                    ->getCriteres()
                    ->map(function (Critere $critere) {
                        return $critere->getLabel();
                    })->toArray()
            );
        }

        if (!$signalement->getCriticites()->isEmpty()) {
            $desordres['precisions'] = implode(
                ',',
                $signalement
                    ->getCriticites()
                    ->map(function (Criticite $criticite) {
                        return $criticite->getLabel();
                    })->toArray()
            );
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
            'show_uploaded_file',
            ['filename' => $intervention->getFiles()->first()->getFilename()],
            UrlGeneratorInterface::ABSOLUTE_URL
        ).'?t='.$this->csrfTokenManager->getToken('suivi_signalement_ext_file_view');
    }
}
