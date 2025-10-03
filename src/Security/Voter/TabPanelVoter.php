<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabBodyType;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TabPanelVoter extends Voter
{
    public const string VIEW_TAB_PANEL = 'VIEW_TAB_PANEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW_TAB_PANEL === $attribute && is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas authentifié');

            return false;
        }

        $roles = $user->getRoles();
        $accessConfig = $this->getAccessConfig();

        if (!isset($accessConfig[$subject])) {
            $vote?->addReason(sprintf('Aucun accès configuré pour le panel "%s".', $subject));

            return false;
        }

        if (!empty(array_filter($roles, fn ($role) => in_array($role, $accessConfig[$subject])))) {
            return true;
        }

        if (in_array('USER_PERMISSION_AFFECTATION', $accessConfig[$subject], true) && $user->hasPermissionAffectation()) {
            return true;
        }

        $vote?->addReason('Aucun droit trouvé pour accéder à ce panel.');

        return false;
    }

    /**
     * @return array<string, array<string>>
     */
    private function getAccessConfig(): array
    {
        return [
            TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY', 'ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_NEW_AFFECTATION => ['ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_NO_AGENT => ['ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_NOUVEAUX => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY', 'ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY'],
            TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY', 'ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
            TabBodyType::TAB_DATA_TYPE_SANS_ACTIVITE_PARTENAIRE => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY', 'ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
            TabBodyType::TAB_DATA_TYPE_ADRESSE_EMAIL_A_VERIFIER => ['ROLE_ADMIN', 'ROLE_ADMIN_TERRITORY', 'ROLE_ADMIN_PARTNER', 'ROLE_USER_PARTNER'],
        ];
    }
}
