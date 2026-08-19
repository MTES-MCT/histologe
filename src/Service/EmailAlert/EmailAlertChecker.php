<?php

namespace App\Service\EmailAlert;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\Query\EmailAlert\PartnerQuery;
use App\Repository\Query\EmailAlert\UserQuery;
use Twig\Extension\RuntimeExtensionInterface;

class EmailAlertChecker implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly PartnerQuery $partnerQuery,
        private readonly UserQuery $userQuery,
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

            $partnerEmailAlerts[(int) $partnerId] = $this->partnerQuery->shouldDisplayAlertEmailIssue(
                $signalement,
                $partner
            );
        }

        return $partnerEmailAlerts;
    }

    public function hasUsagerEmailAlert(string $typeUsager = UserQuery::OCCUPANT, ?string $email = null): bool
    {
        if (null !== $email) {
            return $this->userQuery->shouldDisplayAlertEmailIssue($typeUsager, $email);
        }

        return false;
    }

    public function hasPartnerEmailAlert(?string $email = null): bool
    {
        if (null !== $email) {
            return $this->partnerQuery->shouldDisplayAlertEmailIssueByEmail($email);
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

        $emailsWithIssue = $this->userQuery->findEmailsWithIssue(array_keys($emails));

        return array_fill_keys($emailsWithIssue, true);
    }
}
