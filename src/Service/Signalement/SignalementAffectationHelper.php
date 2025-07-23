<?php

namespace App\Service\Signalement;

use App\Dto\SignalementAffectationListView;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\UserStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;

class SignalementAffectationHelper
{
    /**
     * @param array<mixed> $data
     */
    public static function getStatusLabelFrom(User $user, array $data): string
    {
        $affectations = self::parseAffectations($data['rawAffectations']);
        if (empty($affectations) || ($user->isSuperAdmin() || $user->isTerritoryAdmin())) {
            return $data['statut']->label();
        }
        $statusAffectation = null;
        foreach ($user->getPartners() as $partner) {
            // use id rather than name to be cleaner
            if (isset($affectations[$partner->getNom()])) {
                $statusAffectation = $affectations[$partner->getNom()]['statut'];
                break;
            }
        }

        $affectationStatusLabel = '';
        if (!empty($statusAffectation) || 0 === $statusAffectation) {
            $affectationStatusLabel = AffectationStatus::tryFrom($statusAffectation)?->label();
        }

        return $affectationStatusLabel;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public static function getStatusAndAffectationFrom(User $user, array $data): array
    {
        $affectations = self::parseAffectations($data['rawAffectations']);
        if (empty($affectations) || ($user->isSuperAdmin() || $user->isTerritoryAdmin())) {
            return [$data['statut'], $affectations];
        }
        $statusAffectation = null;
        foreach ($user->getPartners() as $partner) {
            // use id rather than name to be cleaner
            if (isset($affectations[$partner->getNom()])) {
                $statusAffectation = $affectations[$partner->getNom()]['statut'];
                break;
            }
        }

        $signalementStatus = null;
        if (!empty($statusAffectation) || 0 === $statusAffectation) {
            $signalementStatus = AffectationStatus::tryFrom($statusAffectation)?->mapSignalementStatus();
        }

        return [$signalementStatus, $affectations];
    }

    /**
     * @param array<mixed> $data
     *
     * @return ?array<mixed>
     */
    public static function getQualificationFrom(array $data): ?array
    {
        if (null !== $data['qualifications']) {
            return explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $data['qualifications']);
        }

        return null;
    }

    /**
     * @param array<mixed> $data
     *
     * @return ?array<mixed>
     */
    public static function getQualificationStatusesFrom(array $data): ?array
    {
        if (null !== $data['qualificationsStatuses']) {
            return explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $data['qualificationsStatuses']);
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    private static function parseAffectations(?string $rawAffectations): array
    {
        if (empty($rawAffectations)) {
            return [];
        }
        $affectations = [];
        $affectationsList = explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $rawAffectations);
        foreach ($affectationsList as $affectationItem) {
            // if the separator is not found, it means that the query is truncated (by the limit length of group_concat)
            if (!str_contains($affectationItem, SignalementAffectationListView::SEPARATOR_CONCAT)) {
                break;
            }
            list($partner, $status) = explode(SignalementAffectationListView::SEPARATOR_CONCAT, $affectationItem);
            if ('' === $status || !$statusAffectation = AffectationStatus::tryFrom($status)) {
                break;
            }
            $affectations[$partner] = [
                'partner' => $partner,
                'statut' => $statusAffectation->value,
            ];
        }

        return $affectations;
    }

    /**
     * @return ?array<mixed>
     */
    public static function parseConclusionProcedure(?string $rawConclusionProcedure): ?array
    {
        if (empty($rawConclusionProcedure)) {
            return null;
        }

        $procedures = explode(';', $rawConclusionProcedure);
        $lastProcedures = explode(',', $procedures[0]);

        return array_map(function ($procedure) {
            return ProcedureType::from($procedure)->label();
        }, $lastProcedures);
    }

    /**
     * @param array<mixed> $data
     */
    public static function getSignalementFromDataForVoter(array $data): Signalement
    {
        $signalement = new Signalement();
        $territory = new Territory();

        $reflectionClass = new \ReflectionClass($territory);
        $property = $reflectionClass->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($territory, $data['territoryId']);

        $signalement->setTerritory($territory);
        $signalement->setStatut($data['statut']);

        return $signalement;
    }

    /**
     * @return array<string, string>
     */
    public static function getAffectationUsers(User $user, Signalement $signalement): array
    {
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        $partnerAgents = [];
        if ($partner) {
            $users = $partner->getUsers()->toArray();
            foreach ($users as $agent) {
                $isInactive = UserStatus::INACTIVE === $agent->getStatut();
                $label = sprintf(
                    '<span>%s %s (%s)</span><br><span class="fr-text--small%s">%s%s</span>',
                    htmlspecialchars($agent->getPrenom()),
                    htmlspecialchars($agent->getNom()),
                    htmlspecialchars($agent->getEmail()),
                    $isInactive ? ' fr-text-default--error' : ' fr-text-default--grey',
                    $isInactive ? '⚠️ ' : '',
                    htmlspecialchars($agent->getRoleLabel().($isInactive ? ' - Compte inactif' : ''))
                );
                $partnerAgents[$label] = (string) $agent->getId();
            }
        }

        return $partnerAgents;
    }
}
