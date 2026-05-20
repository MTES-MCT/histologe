<?php

namespace App\EventListener;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\FailedEmail;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\UserSignalementSubscription;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCacheHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
final readonly class DashboardKpiCacheInvalidationListener
{
    public function __construct(
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Signalement) {
            // NOUVEAUX_DOSSIERS - Dossiers déposés depuis le formulaire usager
            // NOUVEAUX_DOSSIERS - Dossiers déposés depuis un formulaire pro
            $territoryId = $entity->getTerritory()->getId();
            $this->invalidateTerritoryKpiTags(
                TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
                $territoryId
            );
        }

        if ($entity instanceof Affectation) {
            $territoryId = $entity->getTerritory()->getId();
            // NOUVEAUX_DOSSIERS - Dossiers non affectés aux partenaires
            // NOUVEAUX_DOSSIERS - Nouveaux dossiers
            $this->invalidateTerritoryKpiTags(
                TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
                $territoryId
            );

            // DOSSIERS_A_FERMER - Dossiers fermés par tous les partenaires
            $this->invalidateTerritoryKpiTags(
                TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
                $territoryId
            );
        }

        if ($entity instanceof Suivi) {
            $territoryId = $entity->getSignalement()->getTerritory()->getId();

            if (in_array($entity->getCategory(), [
                SuiviCategory::MESSAGE_PARTNER,
                SuiviCategory::SIGNALEMENT_EDITED_BO,
                SuiviCategory::SIGNALEMENT_IS_ACTIVE,
                SuiviCategory::SIGNALEMENT_IS_REOPENED,
                SuiviCategory::INTERVENTION_IS_CREATED,
                SuiviCategory::INTERVENTION_IS_CANCELED,
                SuiviCategory::INTERVENTION_IS_ABORTED,
                SuiviCategory::INTERVENTION_HAS_CONCLUSION,
                SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED,
                SuiviCategory::INTERVENTION_IS_RESCHEDULED,
                SuiviCategory::INTERVENTION_IS_DONE,
                SuiviCategory::INTERVENTION_CONTROLE_IS_CREATED,
                SuiviCategory::INTERVENTION_CONTROLE_IS_RESCHEDULED,
                SuiviCategory::INTERVENTION_CONTROLE_IS_DONE,
                SuiviCategory::INTERVENTION_ARRETE_IS_CREATED,
                SuiviCategory::INTERVENTION_ARRETE_IS_RESCHEDULED,
                SuiviCategory::NEW_DOCUMENT,
                SuiviCategory::AFFECTATION_IS_CLOSED,
            ], true)) {
                // DOSSIERS_A_VERIFIER - Dossier sans activité partenaire
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER,
                    $territoryId
                );
            }

            if (SuiviCategory::MESSAGE_PARTNER == $entity->getCategory()) {
                // DOSSIERS_MESSAGES_PARTNERS - Nouveaux messages
                // DOSSIERS_MESSAGES_PARTNERS - Messages après fermeture
                // DOSSIERS_MESSAGES_PARTNERS - Messages usagers n'ayant pas eu de réponse
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS,
                    $territoryId
                );
            }

            // DOSSIERS_A_FERMER - Dossiers avec au moins 3 relances usager restées sans réponse
            if (SuiviCategory::MESSAGE_USAGER == $entity->getCategory()) {
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
                    $territoryId
                );
            }

            // DOSSIERS_A_FERMER - Demandes de fermeture par l'usager
            if (SuiviCategory::DEMANDE_ABANDON_PROCEDURE == $entity->getCategory()) {
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
                    $territoryId
                );
            }
        }

        if ($entity instanceof FailedEmail) {
            // DOSSIERS_A_VERIFIER - Adresses e-mail à vérifier
            $this->invalidateTerritoryKpiTags(
                TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER,
            );
        }

        if ($entity instanceof UserSignalementSubscription) {
            $this->invalidateTerritoryKpiTags(
                TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Signalement) {
            $territoryId = $entity->getTerritory()->getId();
            if (SignalementStatus::CLOSED === $entity->getStatut()) {
                // DOSSIERS_A_FERMER - Dossiers fermés par tous les partenaires
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
                    $territoryId
                );
            }

            if ($this->hasUsagerEmailChanged($args, $entity)) {
                // DOSSIERS_MESSAGES_USAGERS - Adresses e-mail à vérifier
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS,
                    $territoryId
                );
            }
        }

        if ($entity instanceof Affectation) {
            $territoryId = $entity->getTerritory()->getId();
            if (AffectationStatus::CLOSED === $entity->getStatut()) {
                // DOSSIERS_A_FERMER - Dossiers fermés par tous les partenaires
                // DOSSIERS_A_FERMER -  Dossiers fermés par les communes
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
                    $territoryId
                );
            }

            if (AffectationStatus::ACCEPTED === $entity->getStatut()) {
                // DOSSIERS_A_VERIFIER - Dossier en attente
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER,
                    $territoryId
                );

                // NOUVEAUX_DOSSIERS - Nouveaux dossiers
                $this->invalidateTerritoryKpiTags(
                    TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
                    $territoryId
                );
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function invalidateTerritoryKpiTags(string $kpiName, ?int $territoryId = null): void
    {
        $this->cache->invalidateTags([
            'territory_'.$kpiName.'_all',
            'territory_'.$kpiName.'_'.$territoryId,
        ]);
    }

    private function hasUsagerEmailChanged(PostUpdateEventArgs $args, Signalement $signalement): bool
    {
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($signalement);

        return array_key_exists('mailOccupant', $changeSet)
            || array_key_exists('mailDeclarant', $changeSet);
    }
}
