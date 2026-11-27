<?php

namespace App\Controller\Security;

use App\Service\Mailer\AuthCodeMailer;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Renvoie le code 2FA reçu par e-mail, sans attendre l'expiration de la session.
 * Les chemins commencent par /2fa : l'access_control impose une 2FA en cours.
 * Chaque route appartient au firewall dont elle partage le préfixe (main ou code_suivi).
 */
class TwoFactorResendCodeController extends AbstractController
{
    public const string CSRF_TOKEN_ID = 'two_factor_resend_code';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthCodeMailer $authCodeMailer,
        private readonly RateLimiterFactoryInterface $twoFactorResendCodeLimiter,
    ) {
    }

    #[Route('/2fa-renvoyer-code', name: '2fa_resend_code', methods: ['POST'])]
    public function resendCode(Request $request): RedirectResponse
    {
        return $this->handleResend($request, '2fa_login');
    }

    #[Route('/2fa-suivi-renvoyer-code', name: '2fa_suivi_resend_code', methods: ['POST'])]
    public function resendCodeSuivi(Request $request): RedirectResponse
    {
        return $this->handleResend($request, '2fa_suivi_login');
    }

    private function handleResend(Request $request, string $formRoute): RedirectResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$token instanceof TwoFactorTokenInterface || !$user instanceof TwoFactorInterface) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', [
                'title' => 'Erreur',
                'message' => 'Votre demande n\'a pas pu être traitée. Merci de réessayer.',
            ]);

            return $this->redirectToRoute($formRoute);
        }

        $limiter = $this->twoFactorResendCodeLimiter->create($user->getUserIdentifier().'-'.$request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            $this->addFlash('error', [
                'title' => 'Trop de demandes',
                'message' => 'Le code a déjà été renvoyé plusieurs fois. Merci de patienter quelques minutes avant de le redemander.',
            ]);

            return $this->redirectToRoute($formRoute);
        }

        // Renvoie le code en cours (équivalent de CodeGenerator::reSend(), absent de l'interface).
        $this->authCodeMailer->sendAuthCode($user);
        $this->addFlash('success', [
            'title' => 'Code renvoyé',
            'message' => 'Le code de vérification vous a été renvoyé par e-mail.',
        ]);

        return $this->redirectToRoute($formRoute);
    }
}
