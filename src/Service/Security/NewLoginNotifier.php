<?php

namespace App\Service\Security;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\TimezoneProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Notifie par e-mail un utilisateur lorsqu'il se connecte depuis un environnement
 * (adresse IP + navigateur) différent de celui de sa dernière connexion.
 *
 * L'environnement n'est pas stocké en clair : seule une empreinte HMAC est conservée.
 * L'empreinte est enregistrée pour tous les agents, mais la notification n'est envoyée
 * qu'aux super admins tant que NEW_LOGIN_NOTIFICATION_TO_ALL_USERS est désactivé.
 */
class NewLoginNotifier
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly TimezoneProvider $timezoneProvider,
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
        #[Autowire(env: 'bool:NEW_LOGIN_NOTIFICATION_TO_ALL_USERS')]
        private readonly bool $notifyAllUsers,
    ) {
    }

    /**
     * Met à jour l'empreinte de connexion de l'utilisateur (sans flush)
     * et envoie une notification si l'environnement a changé.
     */
    public function handleLogin(User $user, Request $request): void
    {
        if (!$this->isAgent($user)) {
            return;
        }

        $ipAddress = $request->getClientIp() ?? '';
        $userAgent = (string) $request->headers->get('User-Agent', '');
        $fingerprint = $this->computeFingerprint($ipAddress, $userAgent);

        $previousFingerprint = $user->getLastLoginFingerprint();
        if ($previousFingerprint === $fingerprint) {
            return;
        }

        $user->setLastLoginFingerprint($fingerprint);

        // Pas de notification sans empreinte précédente : rien à comparer.
        if (null === $previousFingerprint || !$this->isNotificationEnabledFor($user)) {
            return;
        }

        $loginAt = new \DateTimeImmutable('now', new \DateTimeZone($this->timezoneProvider->getTimezone($user)));
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_NEW_LOGIN,
                to: $user->getEmail(),
                user: $user,
                params: [
                    'login_date' => $loginAt->format('d/m/Y à H:i'),
                    'ip_address' => '' !== $ipAddress ? $ipAddress : 'inconnue',
                    'user_agent' => '' !== $userAgent ? $userAgent : 'inconnu',
                ],
            )
        );
    }

    /**
     * Quand on supprimera NEW_LOGIN_NOTIFICATION_TO_ALL_USERS,
     * supprimer cette méthode et notifier tous les agents.
     */
    private function isNotificationEnabledFor(User $user): bool
    {
        return $this->notifyAllUsers || $user->isSuperAdmin();
    }

    private function isAgent(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isTerritoryAdmin()
            || $user->isPartnerAdmin()
            || $user->isUserPartner();
    }

    private function computeFingerprint(string $ipAddress, string $userAgent): string
    {
        return hash_hmac('sha256', $ipAddress.'|'.$userAgent, $this->secret);
    }
}
