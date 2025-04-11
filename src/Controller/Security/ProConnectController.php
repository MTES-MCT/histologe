<?php

namespace App\Controller\Security;

use App\Repository\UserRepository;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/proconnect/callback', name: 'app_user_proconnect_callback')]
    public function handleProConnectCallback(Request $request): Response
    {
        try {
            $params = $request->query->all();
            $proConnectUser = $this->proConnectAuthentication->authenticateFromCallback($params);

            $user = $this->userRepository->findByProConnectUser($proConnectUser);

            if ($user) {
                if ($user->getProConnectUid() !== $proConnectUser->uid) {
                    $user->setProConnectUid($proConnectUser->uid);
                    $this->entityManager->flush();
                }

                $this->security->login(
                    $user,
                    'App\Security\FormLoginAuthenticator',
                    'main'
                );
                $this->proConnectContext->clear();

                $this->addFlash('success', 'Connexion réussie depuis votre compte ProConnect !');

                return $this->redirectToRoute('back_dashboard');
            }

            $this->addFlash('error',
                'Aucun compte actif associé à votre adresse email ou identifiant ProConnect n’a été trouvé.'.
                'Veuillez contacter un administrateur si nécessaire.');
            $this->logger->warning('Tentative de connexion ProConnect refusée : aucun utilisateur actif trouvé.', [
                'email' => $proConnectUser->email,
                'uid' => $proConnectUser->uid,
            ]);
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Erreur lors du traitement de l\'authentification à ProConnect.');
            $this->logger->error('Erreur ProConnect authenticateFromCallback', ['error_message' => $exception->getMessage()]);
        }

        return $this->redirectToRoute('app_login');
    }
}
