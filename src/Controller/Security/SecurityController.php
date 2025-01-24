<?php

namespace App\Controller\Security;

use App\Controller\FileController;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Files\ImageVariantProvider;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(private readonly ImageVariantProvider $imageVariantProvider)
    {
    }

    #[When('dev')]
    #[When('test')]
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
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'api-01@histologe.fr'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'histologe'),
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
    ): BinaryFileResponse|RedirectResponse {
        $request = Request::createFromGlobals();

        if (!$this->isCsrfTokenValid('suivi_signalement_ext_file_view', $request->get('t')) && !$this->isGranted('SIGN_VIEW', $signalement)) {
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

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
