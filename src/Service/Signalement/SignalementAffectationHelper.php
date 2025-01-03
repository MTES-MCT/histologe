<?php

namespace App\Service\Signalement;

use App\Dto\SignalementAffectationListView;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;

class SignalementAffectationHelper
{
    public static function getStatusLabelFrom(User $user, array $data): string
    {
        $affectations = self::parseAffectations($data['rawAffectations']);
        if (empty($affectations) || ($user->isSuperAdmin() || $user->isTerritoryAdmin())) {
            return SignalementStatus::tryFrom($data['statut'])->label();
        }
        $statusAffectation = null;
        foreach ($user->getPartners() as $partner) {
            // use id rather than name to be cleaner
            if (isset($affectations[$partner->getNom()])) {
                $statusAffectation = $affectations[$partner->getNom()]['statut'];
                break;
            }
        }

        return AffectationStatus::tryFrom($statusAffectation)?->label();
    }

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

        return [AffectationStatus::tryFrom($statusAffectation)?->mapSignalementStatus(), $affectations];
    }

    public static function getQualificationFrom(array $data): ?array
    {
        if (null !== $data['qualifications']) {
            return explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $data['qualifications']);
        }

        return null;
    }

    public static function getQualificationStatusesFrom(array $data): ?array
    {
        if (null !== $data['qualificationsStatuses']) {
            return explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $data['qualificationsStatuses']);
        }

        return null;
    }

    public static function parseAffectations(?string $rawAffectations): ?array
    {
        if (empty($rawAffectations)) {
            return [];
        }
        $affectations = [];
        $affectationsList = explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $rawAffectations);
        foreach ($affectationsList as $affectationItem) {
            list($partner, $status) = explode(SignalementAffectationListView::SEPARATOR_CONCAT, $affectationItem);
            $statusAffectation = AffectationStatus::from($status)->value;
            $affectations[$partner] = [
                'partner' => $partner,
                'statut' => $statusAffectation,
            ];
        }

        return $affectations;
    }

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
}
