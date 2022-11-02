<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Security\BackOfficeAuthenticator;
use App\Service\NotificationService;
use App\Service\Token\ActivationTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class UserAccountController extends AbstractController
{
    #[Route('/activation', name: 'login_activation')]
    public function requestLoginLink(NotificationService $notificationService, LoginLinkHandlerInterface $loginLinkHandler, UserRepository $userRepository, Request $request, MailerInterface $mailer): Response
    {
        $title = 'Activation de votre compte';
        if ($request->isMethod('POST') && $email = $request->request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
                $loginLink = $loginLinkDetails->getUrl();
                $notificationService->send(NotificationService::TYPE_ACCOUNT_ACTIVATION, $email, ['link' => $loginLink], $user->getTerritory());

                return $this->render('security/login_link_sent.html.twig', [
                    'title' => 'Lien de connexion envoyé !',
                    'email' => $email,
                ]);
            }
            $this->addFlash('error', 'Cette adresse ne correspond à aucun compte, verifiez votre saisie');
        }

        // if it's not submitted, render the "login" form
        return $this->render('security/login_activation.html.twig', [
            'title' => $title,
            'actionTitle' => 'Activation de votre compte',
            'actionText' => "afin d'activer",
        ]);
    }

    #[Route('/bo/activation/all', name: 'login_activation_all')]
    public function requestLoginLinkAll(NotificationService $notificationService, LoginLinkHandlerInterface $loginLinkHandler, UserRepository $userRepository, Request $request, MailerInterface $mailer): Response
    {
        $title = 'Activation de tout les comptes votre compte';
        $count = 0;
        foreach ($userRepository->findAll() as $user) {
            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
            $loginLink = $loginLinkDetails->getUrl();
            $notificationService->send(NotificationService::TYPE_ACCOUNT_ACTIVATION, $user->getEmail(), ['link' => $loginLink], $user->getTerritory());
            ++$count;
        }

        return $this->json(['response' => $count.' Mails envoyés']);
    }

    #[Route('/activation-incorrecte', name: 'login_activation_fail')]
    public function activationFail(): Response
    {
        $this->addFlash('error', 'Le lien utilisé est invalide ou expiré, veuillez en generer un nouveau');

        return $this->forward('App\Controller\Security\UserAccountController::requestLoginLink');
    }

    #[Route('/mot-de-pass-perdu', name: 'login_mdp_perdu')]
    public function requestNewPass(LoginLinkHandlerInterface $loginLinkHandler, UserRepository $userRepository, Request $request, NotificationService $notificationService): Response
    {
        $title = 'Récupération de votre mot de passe';
        if ($request->isMethod('POST') && $email = $request->request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $email]);
            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
            $loginLink = $loginLinkDetails->getUrl();
            $notificationService->send(
                NotificationService::TYPE_LOST_PASSWORD,
                $email,
                ['link' => $loginLink],
                $user->getTerritory()
            );

            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien de récupération envoyé !',
                'email' => $email,
            ]);
        }

        return $this->render('security/reset_password.html.twig', [
            'title' => $title,
            'actionTitle' => 'Récupération de mot de passe',
            'actionText' => "afin de récupèrer l'accès à",
        ]);
    }

    #[Route('/bo/nouveau-mot-de-passe', name: 'login_creation_pass')]
    public function createPassword(Request $request, PasswordHasherFactoryInterface $hasherFactory, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $title = 'Création de votre mot de passe';
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('create_password_'.$this->getUser()->getId(), $request->get('_csrf_token'))) {
            $user = $this->getUser();
            $password = $hasherFactory->getPasswordHasher($user)->hash($request->get('password'));
            $user->setPassword($password);
            $user->setStatut(User::STATUS_ACTIVE);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Votre compte est maintenant activé !');

            return $this->redirectToRoute('back_index');
        }

        return $this->render('security/login_creation_mdp.html.twig', [
            'title' => $title,
        ]);
    }

    #[Route(path: '/activation-compte/{token}', name: 'activate_account', requirements: ['token' => '.+'])]
    public function resetPassword(
        Request $request,
        UserManager $userManager,
        ActivationTokenGenerator $activationTokenGenerator,
        UserAuthenticatorInterface $userAuthenticator,
        BackOfficeAuthenticator $authenticator,
        string $token): RedirectResponse|Response
    {
        /** @var User $user */
        if (false === ($user = $activationTokenGenerator->validateToken($token))) {
            $this->addFlash('error', 'Votre lien est invalide ou expiré');

            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST') &&
            $this->isCsrfTokenValid('create_password_'.$user->getUuid(), $request->get('_csrf_token'))
        ) {
            if ($request->get('password') !== $request->get('password-repeat')) {
                $this->addFlash('error', 'Les mots de passes ne correspondent pas.');

                return $this->render('security/reset_password_new.html.twig', [
                    'email' => $user->getEmail(),
                    'uuid' => $user->getUuid(),
                ]);
            }

            $user = $userManager->resetPassword($user, $request->get('password'));
            $this->addFlash('success', 'Votre compte est maintenant activé !');

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('security/reset_password_new.html.twig', [
            'email' => $user->getEmail(),
            'uuid' => $user->getUuid(),
        ]);
    }
}
