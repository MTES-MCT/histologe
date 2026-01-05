<?php

namespace App\Controller\Security;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\ActivationTokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserAccountController extends AbstractController
{
    #[Route(
        '/activation',
        name: 'login_activation',
    )]
    public function requestLoginLink(
        NotificationMailerRegistry $notificationMailerRegistry,
        UserRepository $userRepository,
        Request $request,
        RateLimiterFactory $loginActivationFormLimiter,
    ): Response {
        if ($request->isMethod('POST') && is_string($email = $request->request->get('email'))) {
            $limiter = $loginActivationFormLimiter->create($email);
            if (false === $limiter->consume(1)->isAccepted()) {
                return $this->render('security/login_link_sent.html.twig', [
                    'title' => 'Lien d\'activation',
                    'message' => 'Vous avez déjà fait plusieurs demandes pour activer votre compte. Veuillez attendre quelques minutes.',
                    'email' => $email,
                    'is_alert' => true,
                ]);
            }

            $user = $userRepository->findAgentByEmail($email, UserStatus::INACTIVE);
            if ($user) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_FO,
                        to: $email,
                        user: $user,
                    )
                );
            }

            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien d\'activation',
                'message' => 'Si un compte inactif existe pour le courriel indiqué, vous allez recevoir un courriel contenant un lien vous pemettant de créer votre mot de passe afin d\'activer votre compte.',
                'email' => $email,
                'is_alert' => false,
            ]);
        }

        return $this->render('security/login_activation.html.twig');
    }

    #[Route(
        '/mot-de-pass-perdu',
        name: 'login_mdp_perdu',
    )]
    public function requestNewPass(
        UserRepository $userRepository,
        Request $request,
        NotificationMailerRegistry $notificationMailerRegistry,
        RateLimiterFactory $loginPasswordFormLimiter,
    ): Response {
        $title = 'Récupération de votre mot de passe';
        if ($request->isMethod('POST') && is_string($email = $request->request->get('email'))) {
            $limiter = $loginPasswordFormLimiter->create($email);
            if (false === $limiter->consume(1)->isAccepted()) {
                return $this->render('security/login_link_sent.html.twig', [
                    'title' => 'Lien de récupération',
                    'message' => 'Vous avez déjà fait plusieurs demandes pour réinitialiser votre mot de passe. Veuillez attendre quelques minutes.',
                    'email' => $email,
                    'is_alert' => true,
                ]);
            }

            $user = $userRepository->findAgentByEmail($email);
            if ($user && UserStatus::ACTIVE === $user->getStatut()) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_LOST_PASSWORD,
                        to: $email,
                        user: $user
                    )
                );
            }

            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien de récupération',
                'message' => 'Si un compte actif existe pour le courriel indiqué, vous allez recevoir un courriel contenant un lien vous permettant de réinitialiser votre mot de passe.',
                'email' => $email,
                'is_alert' => false,
            ]);
        }

        return $this->render('security/reset_password.html.twig', [
            'title' => $title,
        ]);
    }

    #[Route(path: '/activation-compte/{uuid:user}/{token}', name: 'activate_account', requirements: ['token' => '.+'])]
    public function resetPassword(
        Request $request,
        UserManager $userManager,
        ActivationTokenGenerator $activationTokenGenerator,
        ValidatorInterface $validator,
        Security $security,
        User $user,
        string $token,
    ): RedirectResponse|Response {
        if (false === $activationTokenGenerator->validateToken($user, $token)) {
            $this->addFlash('error', ['title' => 'Accès refusé', 'message' => 'Le lien est invalide ou expiré.']);

            return $this->redirectToRoute('app_login');
        }
        if ($security->getUser()) {
            $security->logout(false);
        }
        if ($request->isMethod('POST')
            && $this->isCsrfTokenValid('create_password_'.$user->getUuid(), $request->request->get('_csrf_token'))
        ) {
            if ($request->request->get('password') !== $request->request->get('password-repeat')) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');

                return $this->render('security/reset_password_new.html.twig', ['user' => $user]);
            }
            $user->setPassword($request->request->get('password'));
            $errors = $validator->validate($user, null, ['password']);
            if (\count($errors) > 0) {
                $errorMessage = '<ul>';
                foreach ($errors as $error) {
                    $errorMessage .= '<li>'.$error->getMessage().'</li>';
                }
                $errorMessage .= '</ul>';
                $this->addFlash('error error-raw', $errorMessage);

                return $this->render('security/reset_password_new.html.twig', ['user' => $user]);
            }
            $user = $userManager->resetPassword($user, $request->request->get('password'));
            $message = $user->isApiUser() ? 'Votre compte a bien été activé, vous pouvez utiliser l\'API.' : 'Votre compte a bien été activé, vous pouvez vous connecter.';
            $this->addFlash('success', ['title' => 'Compte activé', 'message' => $message]);
            $request->request->remove('password');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_new.html.twig', ['user' => $user]);
    }
}
