<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\ActivationTokenGenerator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserAccountController extends AbstractController
{
    #[Route('/activation', name: 'login_activation')]
    public function requestLoginLink(
        NotificationMailerRegistry $notificationMailerRegistry,
        UserRepository $userRepository,
        Request $request
    ): Response {
        if ($request->isMethod('POST') && $email = $request->request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user && User::STATUS_INACTIVE === $user->getStatut() && !\in_array('ROLE_USAGER', $user->getRoles())) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_FO,
                        to: $email,
                        territory: $user->getTerritory(),
                        user: $user,
                    )
                );
            }

            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien d\'activation',
                'message' => 'Si un compte inactif existe pour le courriel indiqué, vous allez recevoir un courriel contenant un lien vous pemettant de créer votre mot de passe afin d\'activer votre compte.',
                'email' => $email,
            ]);
        }

        return $this->render('security/login_activation.html.twig');
    }

    #[Route('/mot-de-pass-perdu', name: 'login_mdp_perdu')]
    public function requestNewPass(
        UserRepository $userRepository,
        Request $request,
        NotificationMailerRegistry $notificationMailerRegistry
    ): Response {
        $title = 'Récupération de votre mot de passe';
        if ($request->isMethod('POST') && $email = $request->request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user && User::STATUS_ACTIVE === $user->getStatut() && !\in_array('ROLE_USAGER', $user->getRoles())) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_LOST_PASSWORD,
                        to: $email,
                        territory: $user->getTerritory(),
                        user: $user
                    )
                );
            }

            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien de récupération',
                'message' => 'Si un compte actif existe pour le courriel indiqué, vous allez recevoir un courriel contenant un lien vous permettant de réinitialiser votre mot de passe.',
                'email' => $email,
            ]);
        }

        return $this->render('security/reset_password.html.twig', [
            'title' => $title,
        ]);
    }

    #[Route(path: '/activation-compte/{uuid}/{token}', name: 'activate_account', requirements: ['token' => '.+'])]
    public function resetPassword(
        Request $request,
        UserManager $userManager,
        ActivationTokenGenerator $activationTokenGenerator,
        ValidatorInterface $validator,
        Security $security,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        User $user,
        string $token
    ): RedirectResponse|Response {
        if (false === $activationTokenGenerator->validateToken($user, $token)) {
            $this->addFlash('error', 'Votre lien est invalide ou expiré');

            return $this->redirectToRoute('app_login');
        }
        if ($security->getUser()) {
            $security->logout(false);
        }
        if ($request->isMethod('POST') &&
            $this->isCsrfTokenValid('create_password_'.$user->getUuid(), $request->get('_csrf_token'))
        ) {
            if ($request->get('password') !== $request->get('password-repeat')) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');

                return $this->render('security/reset_password_new.html.twig', ['user' => $user]);
            }
            $user->setPassword($request->get('password'));
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
            $user = $userManager->resetPassword($user, $request->get('password'));
            $this->addFlash('success', 'Votre compte est maintenant activé, vous pouvez vous connecter');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_new.html.twig', ['user' => $user]);
    }
}
