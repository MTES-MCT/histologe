<?php

namespace App\Controller;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\FileRepository;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/signalement')]
class SignalementFileController extends AbstractController
{
    #[Route('/{uuid:signalement}/file/add', name: 'signalement_add_file')]
    public function addFileSignalement(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SignalementFileProcessor $signalementFileProcessor,
        UserManager $userManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);
        if (!$this->isGranted('SIGN_USAGER_EDIT', $signalement) || !$request->isXmlHttpRequest()) {
            return $this->json(['response' => 'Requête incorrecte'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), $request->get('_token')) || !$files = $request->files->get('signalement-add-file')) {
            return $this->json(['response' => 'Token CSRF invalide ou paramètre manquant, veuillez rechargez la page'], Response::HTTP_BAD_REQUEST);
        }
        $inputName = isset($files[File::INPUT_NAME_DOCUMENTS]) ? File::INPUT_NAME_DOCUMENTS : File::INPUT_NAME_PHOTOS;
        $fileList = $signalementFileProcessor->process($files, $inputName);
        if (!$signalementFileProcessor->isValid()) {
            return $this->json(['response' => $signalementFileProcessor->getErrorMessages()], Response::HTTP_BAD_REQUEST);
        }
        $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $request->get('email'));
        $signalementFileProcessor->addFilesToSignalement(fileList: $fileList, signalement: $signalement, user: $user, isTemp : true);
        $entityManager->persist($signalement);
        $entityManager->flush();

        return $this->json(['response' => $signalementFileProcessor->getLastFile()->getId()]);
    }

    #[Route('/{uuid:signalement}/file/edit', name: 'signalement_edit_file')]
    public function editFileSignalement(
        Signalement $signalement,
        Request $request,
        FileRepository $fileRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);
        if (!$this->isGranted('SIGN_USAGER_EDIT', $signalement) || !$request->isXmlHttpRequest()) {
            return $this->json(['response' => 'Requête incorrecte'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->isCsrfTokenValid('signalement_edit_file_'.$signalement->getId(), $request->get('_token'))) {
            return $this->json(['response' => 'Token CSRF invalide, veuillez rechargez la page'], Response::HTTP_BAD_REQUEST);
        }
        $file = $fileRepository->findOneBy(['id' => $request->get('file_id'), 'signalement' => $signalement, 'isTemp' => true]);
        if (null === $file) {
            return $this->json(['response' => 'Document introuvable'], Response::HTTP_BAD_REQUEST);
        }
        $documentType = DocumentType::tryFrom($request->get('documentType'));
        if (null === $documentType || !isset(DocumentType::getOrderedSituationList()[$documentType->name])) {
            return $this->json(['response' => 'Type de document invalide'], Response::HTTP_BAD_REQUEST);
        }
        $file->setDocumentType($documentType);
        $desordreSlug = $request->get('desordreSlug');
        $file->setDesordreSlug($desordreSlug);
        if ($desordreSlug && !$file->getDesordreSlug()) {
            return $this->json(['response' => 'Type de désordre invalide'], Response::HTTP_BAD_REQUEST);
        }
        $entityManager->persist($file);
        $entityManager->flush();

        return $this->json(['response' => 'success']);
    }

    #[Route('/{uuid:signalement}/file/delete-tmp', name: 'signalement_delete_tmpfile', methods: ['DELETE'])]
    public function deleteTmpFile(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
        FileRepository $fileRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);
        $file = $fileRepository->findOneBy(['id' => $request->get('file_id'), 'signalement' => $signalement, 'isTemp' => true]);
        if (!$file) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }
        if (!$uploadHandlerService->deleteFile($file)) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }
        $entityManager->remove($file);
        $entityManager->flush();

        return $this->json(['success' => true, 'fileId' => $request->get('file_id')]);
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/{uuid:signalement}/file/delete', name: 'signalement_delete_file')]
    public function deleteFileSignalement(
        Signalement $signalement,
        Request $request,
        FileRepository $fileRepository,
        UploadHandlerService $uploadHandlerService,
        SuiviManager $suiviManager,
        UserManager $userManager,
    ): Response {
        $fileId = $request->get('file_id');
        $file = $fileRepository->findOneBy(
            [
                'id' => $fileId,
                'signalement' => $signalement,
            ]
        );
        $fromEmail = $request->get('from');
        $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $fromEmail);
        $this->denyAccessUnlessGranted('FRONT_FILE_DELETE', $file);
        if (null === $file) {
            $this->addFlash('error', 'Ce fichier n\'existe plus');
        } elseif ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))) {
            $type = $file->getFileType();
            $filename = $file->getFilename();
            if ($uploadHandlerService->deleteFile($file)) {
                $description = File::FILE_TYPE_DOCUMENT === $type ? 'Document supprimé ' : 'Photo supprimée ';
                $description .= 'par l\'usager :';
                $description .= '<ul><li>'.$filename.'</li></ul>';
                $suiviManager->createSuivi(
                    user: $user,
                    signalement: $signalement,
                    description: $description,
                    type: Suivi::TYPE_AUTO,
                );
                $message = (File::FILE_TYPE_DOCUMENT === $type) ? 'Le document a bien été supprimé.' : 'La photo a bien été supprimée.';
                $this->addFlash('success', $message);
            } else {
                $this->addFlash('error', 'Le fichier n\'a pas été supprimé.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide, veuillez rechargez la page');
        }

        return $this->redirectToRoute(
            'front_suivi_signalement',
            ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
        );
    }
}
