<?php

namespace App\Controller\Security;

use App\Entity\Signalement;
use App\Service\ImageManipulationHandler;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
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
            throw $this->createNotFoundException();
        }
        try {
            $variant = $request->query->get('variant');
            $variantNames = ImageManipulationHandler::getVariantNames($filename);

            if ('thumb' == $variant && $fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_THUMB])) {
                $filename = $variantNames[ImageManipulationHandler::SUFFIX_THUMB];
            } elseif ('resize' == $variant && $fileStorage->fileExists($variantNames[ImageManipulationHandler::SUFFIX_RESIZE])) {
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
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/keep-session-alive', name: 'keep_session_alive')]
    public function keepSessionAlive()
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
