<?php

namespace App\Controller\Security;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use App\Service\Files\ImageVariantProvider;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SymfonySecurity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_SECURE_UUID_URL')]
        private readonly bool $featureSecureUuidUrl,
        #[Autowire(env: 'FEATURE_SUIVI_ACTION')]
        private readonly bool $featureSuiviAction,
    ) {
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[Security(name: null)]
    #[OA\Post(
        path: '/api/login',
        summary: 'Se connecter en utilisant l\'email et le mot de passe',
        security: null,
        requestBody: new OA\RequestBody(
            description: 'Identifiants pour se connecter',
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'api-01@signal-logement.fr'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'signallogement'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'abcd1234'),
                        new OA\Property(property: 'expires_at', type: 'string', example: '2024-12-31T23:59:59Z'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Identifiants invalides',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Identifiants invalides.'),
                        new OA\Property(property: 'message', type: 'string', example: 'Les identifiants sont invalides.'),
                        new OA\Property(property: 'status', type: 'int', example: 401),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function loginApi(
        #[CurrentUser] ?User $user = null,
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'error' => 'Identifiants invalides.',
                'message' => 'Les identifiants sont invalides.',
                'status' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['success']);
    }

    #[Route(
        '/connexion',
        name: 'app_login',
        defaults: ['show_sitemap' => true]
    )]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $title = 'Connexion';
        if ($this->getUser()) {
            return $this->redirectToRoute('back_dashboard');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['title' => $title, 'last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * Use only for exporting pdf signalement and using in old description suivi
     * Use @see FileController::showFile() instead.
     */
    #[Route('/_up/{filename}/{uuid:signalement?}', name: 'show_uploaded_file')]
    public function showUploadedFile(
        LoggerInterface $logger,
        ImageVariantProvider $imageVariantProvider,
        string $filename,
        ?Signalement $signalement = null,
    ): Response {
        $request = Request::createFromGlobals();

        if (
            !$this->isCsrfTokenValid('suivi_signalement_ext_file_view', $request->get('t'))
            && !$this->isGranted('SIGN_VIEW', $signalement)
        ) {
            throw $this->createAccessDeniedException();
        }
        try {
            $variant = $request->query->get('variant');
            $file = $imageVariantProvider->getFileVariant($filename, $variant);

            return new BinaryFileResponse($file);
        } catch (\Throwable $exception) {
            $logger->error($exception->getMessage());
        }

        return new BinaryFileResponse(
            new File($this->getParameter('images_dir').'image-404.png'),
        );
    }

    /**
     * Use only for exporting pdf usager.
     */
    #[Route('/show-export-pdf-usager/{filename}/{code}', name: 'show_export_pdf_usager')]
    public function showExportPdfUsager(
        LoggerInterface $logger,
        ImageVariantProvider $imageVariantProvider,
        string $filename,
        string $code,
        SignalementRepository $signalementRepository,
        SymfonySecurity $security,
        AuthenticationUtils $authenticationUtils,
    ): Response {
        $request = Request::createFromGlobals();

        if ($signalement = $signalementRepository->findOneByCodeForPublic($code, false)) {
            if ($this->featureSecureUuidUrl && $this->featureSuiviAction) { // TODO Remove FEATURE_SECURE_UUID_URL
                /** @var ?SignalementUser $currentUser */
                $currentUser = $security->getUser();
                if (!$security->isGranted('ROLE_SUIVI_SIGNALEMENT') || $currentUser?->getCodeSuivi() !== $code) {
                    // get the login error if there is one
                    $error = $authenticationUtils->getLastAuthenticationError();

                    return $this->render('security/login_suivi_signalement.html.twig', [
                        'signalement' => $signalement,
                        'fromEmail' => $request->get('from'),
                        'error' => $error,
                    ]);
                }
            } else {
                throw $this->createAccessDeniedException();
            }

            try {
                $file = $imageVariantProvider->getFileVariant($filename);

                return new BinaryFileResponse($file);
            } catch (\Throwable $exception) {
                $logger->error($exception->getMessage());
            }

            return new BinaryFileResponse(
                new File($this->getParameter('images_dir').'image-404.png'),
            );
        }

        $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

        return $this->render('front/flash-messages.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/logout-suivi', name: 'app_logout_signalement_user')]
    public function logoutSignalementUser(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/logout-suivi-success', name: 'app_logout_signalement_user_success')]
    public function logoutSignalementUserSuccess(): Response
    {
        return $this->redirectToRoute('home');
    }
}
