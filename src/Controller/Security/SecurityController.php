<?php

namespace App\Controller\Security;

use App\Entity\Signalement;
use App\Entity\User;
use App\Service\ImageManipulationHandler;
use League\Flysystem\FilesystemOperator;
use Nelmio\ApiDocBundle\Annotation\Security;
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
    #[When('dev')]
    #[When('test')]
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[Security(name: null)]
    #[OA\Post(
        path: '/api/login',
        summary: 'Login using email and password',
        security: null,
        requestBody: new OA\RequestBody(
            description: 'Credentials for logging in',
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
                description: 'Successful login',
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
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Invalid credentials.'),
                        new OA\Property(property: 'message', type: 'string', example: 'The credentials are invalid.'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function loginApi(
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials.',
                'message' => 'The credentials are invalid.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['success']);
    }

    #[Route('/connexion', name: 'app_login')]
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

    #[Route('/_up/{filename}/{uuid?}', name: 'show_uploaded_file')]
    public function showUploadedFile(
        LoggerInterface $logger,
        FilesystemOperator $fileStorage,
        string $filename,
        ?Signalement $signalement = null,
    ): BinaryFileResponse|RedirectResponse {
        $request = Request::createFromGlobals();

        if (!$this->isCsrfTokenValid('suivi_signalement_ext_file_view', $request->get('t')) && !$this->isGranted('SIGN_VIEW', $signalement)) {
            throw $this->createAccessDeniedException();
        }
        try {
            $variant = $request->query->get('variant');
            $variantNames = ImageManipulationHandler::getVariantNames($filename);

            if ('thumb' == $variant && $fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
                $filename = $variantNames[ImageManipulationHandler::SUFFIX_THUMB];
            } elseif ($fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])) {
                $filename = $variantNames[ImageManipulationHandler::SUFFIX_RESIZE];
            }
            if (!$fileStorage->fileExists($filename)) {
                throw new \Exception('File "'.$filename.'" not found');
            }
            $tmpFilepath = $this->getParameter('uploads_tmp_dir').$filename;
            $bucketFilepath = $this->getParameter('url_bucket').'/'.$filename;
            $content = file_get_contents($bucketFilepath);
            file_put_contents($tmpFilepath, $content);
            $file = new File($tmpFilepath);

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
