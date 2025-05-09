<?php

namespace App\Controller\Security;

use App\Entity\Enum\UserStatus;
use App\Repository\UserRepository;
use App\Security\FormLoginAuthenticator;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Service\Gouv\ProConnect\Request\CallbackRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class ProConnectController extends AbstractController
{
    public function __construct(
        private readonly ProConnectAuthentication $proConnectAuthentication,
        private readonly ProConnectContext $proConnectContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
        #[Autowire(env: 'FEATURE_PROCONNECT')]
        private readonly int $featureProConnect,
    ) {
        if (!$this->featureProConnect) {
            throw new \RuntimeException('ProConnect feature is disabled');
        }
    }

    #[Route('/proconnect/login', name: 'app_user_proconnect_login', methods: ['POST'])]
    public function loginWithProConnect(LoggerInterface $logger): Response
    {
        try {
            $url = $this->proConnectAuthentication->getAuthorizationUrl();

            return new RedirectResponse($url);
        } catch (\Throwable $exception) {
            $logger->error('Erreur ProConnect getAuthorizationUrl', [
                'exception' => $exception,
            ]);

            $this->addFlash('error', 'Une erreur est survenue lors de la connexion à ProConnect');

            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/proconnect/login-callback', name: 'app_user_proconnect_login_callback')]
    public function handleProConnectCallback(
        #[MapQueryString]
        CallbackRequest $request,
    ): Response {
        try {
            $proConnectUser = $this->proConnectAuthentication->authenticateFromCallback($request);
            $user = $this->userRepository->findByProConnectUser($proConnectUser);
            if ($user) {
                if ($user->getProConnectUserId() !== $proConnectUser->sub) {
                    $user->setProConnectUserId($proConnectUser->sub);
                    if (UserStatus::INACTIVE === $user->getStatut()) {
                        $user->setStatut(UserStatus::ACTIVE);
                    }
                    $this->entityManager->flush();
                }

                $this->security->login(
                    $user,
                    FormLoginAuthenticator::class,
                    'main'
                );

                $this->addFlash('success', 'Connexion réussie depuis votre compte ProConnect !');

                return $this->redirectToRoute('back_dashboard');
            }

            $this->addFlash('warning',
                'L\'adresse e-mail liée à votre compte ProConnect ne correspond à aucun compte sur la plateforme Signal Logement.'
                        .' Merci de contacter votre responsable de territoire.');
            $this->logger->warning('Tentative de connexion ProConnect refusée : aucun utilisateur actif trouvé.', [
                'email' => $proConnectUser->email,
                'sub' => $proConnectUser->sub,
                'uid' => $proConnectUser->uid,
            ]);
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Erreur lors du traitement de l\'authentification à ProConnect.');
            $this->logger->error('Erreur ProConnect authenticateFromCallback', ['error_message' => $exception->getMessage()]);
        }

        $this->proConnectContext->clearSession();

        return $this->redirectToRoute('app_login');
    }
}
