<?php

namespace App\Service;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\Query\EmailAlert\PartnerQueryService;
use App\Repository\Query\EmailAlert\UserQueryService;
use Twig\Extension\RuntimeExtensionInterface;

class EmailAlertChecker implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly PartnerQueryService $partnerQueryService,
        private readonly UserQueryService $userQueryService,
    ) {
    }

    /**
     * @return array<int, bool>
     */
    public function buildPartnerEmailAlert(Signalement $signalement): array
    {
        $partnerEmailAlerts = [];

        foreach ($signalement->getAffectations() as $affectation) {
            $partner = $affectation->getPartner();
            $partnerId = $partner->getId();

            $partnerEmailAlerts[(int) $partnerId] = $this->partnerQueryService->shouldDisplayAlertEmailIssue(
                $signalement,
                $partner
            );
        }

        return $partnerEmailAlerts;
    }

    public function hasUsagerEmailAlert(string $typeUsager = UserQueryService::OCCUPANT, ?string $email = null): bool
    {
        if (null !== $email) {
            return $this->userQueryService->shouldDisplayAlertEmailIssue($typeUsager, $email);
        }

        return false;
    }

    public function hasPartnerEmailAlert(?string $email = null): bool
    {
        if (null !== $email) {
            return $this->partnerQueryService->shouldDisplayAlertEmailIssueByEmail($email);
        }

        return false;
    }

    /**
     * @param iterable<User> $users
     *
     * @return array<string, true>
     */
    public function buildUserEmailAlert(iterable $users): array
    {
        $emails = [];
        foreach ($users as $user) {
            $emails[$user->getEmail()] = false;
        }

        if ([] === $emails) {
            return [];
        }

        $emailsWithIssue = $this->userQueryService->findEmailsWithIssue(array_keys($emails));

        return array_fill_keys($emailsWithIssue, true);
    }
}
