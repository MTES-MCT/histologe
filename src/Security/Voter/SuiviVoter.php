<?php

namespace App\Security\Voter;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Suivi>
 */
class SuiviVoter extends Voter
{
    public const string DELETE_SUIVI = 'DELETE_SUIVI';
    public const string EDIT_SUIVI = 'EDIT_SUIVI';

    public function __construct(
        #[Autowire(env: 'EDITION_SUIVI_ENABLE')]
        private readonly bool $editionSuiviEnable,
        #[Autowire(env: 'DELAY_SUIVI_EDITABLE_IN_MINUTES')]
        private readonly int $delaySuiviEditableInMinutes,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::DELETE_SUIVI, self::EDIT_SUIVI]) && ($subject instanceof Suivi);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié.');

            return false;
        }

        return match ($attribute) {
            self::DELETE_SUIVI => $this->canDelete($subject, $user),
            self::EDIT_SUIVI => $this->canEdit($subject, $user),
            default => false,
        };
    }

    private function canDelete(Suivi $suivi, User $user): bool
    {
        if (null !== $suivi->getDeletedAt()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->canEdit($suivi, $user);
    }

    private function canEdit(Suivi $suivi, User $user): bool
    {
        if (!$this->editionSuiviEnable) {
            return false;
        }
        if (null !== $suivi->getDeletedAt()) {
            return false;
        }
        $limit = new \DateTimeImmutable('-'.$this->delaySuiviEditableInMinutes.' minutes');
        if (SuiviCategory::MESSAGE_PARTNER === $suivi->getCategory() && $suivi->isWaitingNotification() && $suivi->getCreatedAt() > $limit && $user === $suivi->getCreatedBy()) {
            return true;
        }

        return false;
    }
}
