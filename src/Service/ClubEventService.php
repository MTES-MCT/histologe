<?php

namespace App\Service;

use App\Entity\ClubEvent;
use App\Entity\User;
use App\Repository\ClubEventRepository;

class ClubEventService
{
    public function __construct(
        private readonly ClubEventRepository $clubEventRepository,
    ) {
    }

    public function getNextClubEventForUser(User $user): ?ClubEvent
    {
        $clubEvents = $this->clubEventRepository->findInFuture();

        foreach ($clubEvents as $clubEvent) {
            if ($user->isSuperAdmin()) {
                return $clubEvent;
            }
            // Les RT ont accès à tous les évènements correspondant à leur rôle
            if ($user->isTerritoryAdmin() && $this->isRoleMatch($clubEvent, $user)) {
                return $clubEvent;
            }
            // Les autres utilisateurs doivent correspondre aux critères de rôle, type et compétence
            if ($this->isRoleMatch($clubEvent, $user) && $this->isPartnerTypeMatch($clubEvent, $user) && $this->isPartnerCompetenceMatch($clubEvent, $user)) {
                return $clubEvent;
            }
        }

        return null;
    }

    private function isRoleMatch(ClubEvent $clubEvent, User $user): bool
    {
        if (empty($clubEvent->getUserRoles())) {
            return true;
        }

        foreach ($clubEvent->getUserRoles() as $role) {
            if ($user->isTerritoryAdmin() && User::ROLE_ADMIN_TERRITORY === $role) {
                return true;
            }
            if ($user->isPartnerAdmin() && User::ROLE_ADMIN_PARTNER === $role) {
                return true;
            }
            if ($user->isUserPartner() && User::ROLE_USER_PARTNER === $role) {
                return true;
            }
        }

        return false;
    }

    private function isPartnerTypeMatch(ClubEvent $clubEvent, User $user): bool
    {
        if (empty($clubEvent->getPartnerTypes())) {
            return true;
        }

        $userPartnerTypes = [];
        foreach ($user->getPartners() as $partner) {
            $userPartnerTypes[] = $partner->getType();
        }

        foreach ($clubEvent->getPartnerTypes() as $partnerType) {
            if (in_array($partnerType, $userPartnerTypes, true)) {
                return true;
            }
        }

        return false;
    }

    private function isPartnerCompetenceMatch(ClubEvent $clubEvent, User $user): bool
    {
        if (empty($clubEvent->getPartnerCompetences())) {
            return true;
        }

        $userPartnerCompetences = [];
        foreach ($user->getPartners() as $partner) {
            $userPartnerCompetences = array_merge($userPartnerCompetences, $partner->getCompetence());
        }

        foreach ($clubEvent->getPartnerCompetences() as $partnerCompetence) {
            if (in_array($partnerCompetence, $userPartnerCompetences, true)) {
                return true;
            }
        }

        return false;
    }
}
