<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Security\User\SignalementUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $logoutUrl = null;

        if ($user instanceof SignalementUser) {
            $this->logger->info('App usager logout');
            $this->clearSession($session);
            $response = new RedirectResponse(
                $this->urlGenerator->generate(
                    'home',
                )
            );
            $event->setResponse($response);

            return;
        }

        try {
            if ($user?->getProConnectUserId() && $session->has(ProConnectContext::SESSION_KEY_ID_TOKEN)) {
                $logoutUrl = $this->proConnectAuthentication->getLogoutUrl();
                $this->clearSession($session);
                if ($logoutUrl) {
                    $this->logger->info('ProConnect logout');
                    $event->setResponse(new RedirectResponse($logoutUrl));

                    return;
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Erreur ProConnect getLogoutUrl', [
                'exception' => $exception,
            ]);
            if ($logoutUrl) {
                $event->setResponse(new RedirectResponse($logoutUrl));
            }

            if ($session->has(ProConnectContext::SESSION_KEY_ID_TOKEN)) {
                $flashBag = $event->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
                $flashBag->add('warning',
                    'Vous êtes bien déconnecté de l\'application, mais la déconnexion de votre compte ProConnect '
                    .'n\'a pas pu être effectuée automatiquement. '
                    .'Veuillez penser à fermer manuellement votre session sur ProConnect.'
                );
            }
            $this->clearSession($session);
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));

            return;
        }
        $this->logger->info('App logout');
        $this->clearSession($session);
    }

    private function clearSession(SessionInterface $session): void
    {
        $this->logger->info('Clearing session');
        $this->proConnectContext->clearSession();
        $session->invalidate();
    }
}
