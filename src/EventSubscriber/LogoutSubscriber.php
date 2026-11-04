<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Security\User\ServiceSecoursUser;
use App\Security\User\SignalementBailleur;
use App\Security\User\SignalementUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Utils\UrlHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ProConnectAuthentication $proConnectAuthentication,
        private ProConnectContext $proConnectContext,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        /** @var ?User $user */
        $user = $token?->getUser();
        $request = $event->getRequest();
        $session = $request->getSession();

        if ($user instanceof SignalementUser) {
            $this->clearSession($session);
            $response = new RedirectResponse(
                $this->urlGenerator->generate(
                    'home',
                )
            );
            $event->setResponse($response);

            return;
        }
        if ($user instanceof SignalementBailleur) {
            $this->clearSession($session);
            $response = new RedirectResponse($this->urlGenerator->generate('app_login_bailleur'));
            $event->setResponse($response);

            return;
        }

        if ($user instanceof ServiceSecoursUser) {
            $domain = UrlHelper::extractRootDomain($request->getHost());
            $this->handleProConnectLogout(
                $event,
                $session,
                'Erreur ProConnect getLogoutUrl Service Secours',
                'service_secours_home',
                ['domain' => $domain],
            );

            return;
        }

        if ($user?->getProConnectUserId()) {
            $this->handleProConnectLogout(
                $event,
                $session,
                'Erreur ProConnect getLogoutUrl',
                'app_login',
            );

            return;
        }

        $this->clearSession($session);
    }

    /**
     * @param array<string, mixed> $fallbackRouteParams
     */
    private function handleProConnectLogout(
        LogoutEvent $event,
        SessionInterface $session,
        string $errorMessage,
        ?string $fallbackRoute = null,
        array $fallbackRouteParams = [],
    ): void {
        if ($session->has(ProConnectContext::SESSION_KEY_ID_TOKEN)) {
            try {
                $logoutUrl = $this->proConnectAuthentication->getLogoutUrl();
                $this->clearSession($session);
                if ($logoutUrl) {
                    $event->setResponse(new RedirectResponse($logoutUrl));

                    return;
                }
            } catch (\Throwable $exception) {
                $this->logger->error($errorMessage, [
                    'exception' => $exception,
                ]);
                /** @var Session $session */
                $session = $event->getRequest()->getSession();
                $flashBag = $session->getFlashBag();
                $flashBag->add(
                    'warning',
                    'Vous êtes bien déconnecté de l\'application, mais la déconnexion de votre compte ProConnect '
                    .'n\'a pas pu être effectuée automatiquement. '
                    .'Veuillez penser à fermer manuellement votre session sur ProConnect.'
                );
            }
        }

        $this->clearSession($session);

        if ($fallbackRoute) {
            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate($fallbackRoute, $fallbackRouteParams)
            ));
        }
    }

    private function clearSession(SessionInterface $session): void
    {
        $this->proConnectContext->clearSession();
        $session->invalidate();
    }
}
