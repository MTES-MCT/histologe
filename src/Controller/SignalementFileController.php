<?php

namespace App\Controller;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/signalement')]
class SignalementFileController extends AbstractController
{
    #[Route('/{code}/file/add', name: 'signalement_add_file')]
    public function addFileSignalement(
        string $code,
        Request $request,
        EntityManagerInterface $entityManager,
        SignalementFileProcessor $signalementFileProcessor,
        SignalementRepository $signalementRepository,
    ): JsonResponse {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['response' => 'Requête incorrecte'], Response::HTTP_BAD_REQUEST);
        }
        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();
        if (!$this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), $request->get('_token')) || !$files = $request->files->get('signalement-add-file')) {
            return $this->json(['response' => 'Token CSRF invalide ou paramètre manquant, veuillez recharger la page'], Response::HTTP_BAD_REQUEST);
        }
        $fileList = $signalementFileProcessor->process($files);
        if (!$signalementFileProcessor->isValid()) {
            return $this->json(['response' => $signalementFileProcessor->getErrorMessages()], Response::HTTP_BAD_REQUEST);
        }
        $signalementFileProcessor->addFilesToSignalement(fileList: $fileList, signalement: $signalement, user: $signalementUser->getUser(), isTemp : true);
        $entityManager->persist($signalement);
        $entityManager->flush();

        return $this->json(['response' => $signalementFileProcessor->getLastFile()->getId()]);
    }

    #[Route('/{code}/file/edit', name: 'signalement_edit_file')]
    public function editFileSignalement(
        string $code,
        Request $request,
        FileRepository $fileRepository,
        EntityManagerInterface $entityManager,
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): JsonResponse {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['response' => 'Requête incorrecte'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->isCsrfTokenValid('signalement_edit_file_'.$signalement->getId(), $request->get('_token'))) {
            return $this->json(['response' => 'Token CSRF invalide, veuillez recharger la page'], Response::HTTP_BAD_REQUEST);
        }
        $file = $fileRepository->findOneBy(['id' => $request->get('file_id'), 'signalement' => $signalement, 'isTemp' => true]);
        if (null === $file) {
            return $this->json(['response' => 'Document introuvable'], Response::HTTP_BAD_REQUEST);
        }
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $documentType = DocumentType::tryFrom($request->get('documentType'));
        if ($request->get('documentType') && isset($infoDesordres['criteres'][$request->get('documentType')])) {
            $file->setDocumentType(DocumentType::PHOTO_SITUATION);
            $file->setDesordreSlug($request->get('documentType'));
        } elseif (null === $documentType || !isset(DocumentType::getOrderedSituationList()[$documentType->name])) {
            return $this->json(['response' => 'Type de document invalide'], Response::HTTP_BAD_REQUEST);
        } else {
            $file->setDocumentType($documentType);
            $file->setDesordreSlug(null);
        }
        $entityManager->persist($file);
        $entityManager->flush();

        return $this->json(['response' => 'success']);
    }

    #[Route('/{code}/file/delete-tmp', name: 'signalement_delete_tmpfile', methods: ['DELETE'])]
    public function deleteTmpFile(
        string $code,
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
        FileRepository $fileRepository,
        SignalementRepository $signalementRepository,
    ): JsonResponse {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
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
    #[Route('/{code}/file/delete', name: 'signalement_delete_file')]
    public function deleteFileSignalement(
        string $code,
        Request $request,
        FileRepository $fileRepository,
        UploadHandlerService $uploadHandlerService,
        SuiviManager $suiviManager,
        SignalementRepository $signalementRepository,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);
        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        $fileId = $request->get('file_id');
        $file = $fileRepository->findOneBy(['id' => $fileId, 'signalement' => $signalement]);
        $this->denyAccessUnlessGranted('FRONT_FILE_DELETE', $file);

        if (null === $file) {
            $this->addFlash('error', 'Ce fichier n\'existe plus');
        } elseif ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))) {
            $filename = $file->getFilename();
            if ($uploadHandlerService->deleteFile($file)) {
                $description = $file->isTypeDocument() ? 'Document supprimé ' : 'Photo supprimée ';
                $description .= 'par l\'usager :';
                $description .= '<ul><li>'.$filename.'</li></ul>';
                $suiviManager->createSuivi(
                    signalement: $signalement,
                    description: $description,
                    type: Suivi::TYPE_AUTO,
                    category: SuiviCategory::DOCUMENT_DELETED_BY_USAGER,
                    user: $signalementUser->getUser(),
                );
                $message = $file->isTypeDocument() ? 'Le document a bien été supprimé.' : 'La photo a bien été supprimée.';
                $this->addFlash('success', $message);
            } else {
                $this->addFlash('error', 'Le fichier n\'a pas été supprimé.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide, veuillez recharger la page');
        }

        return $this->redirectToRoute('front_suivi_signalement_documents', ['code' => $signalement->getCodeSuivi()]);
    }

    #[Route('/{code}/file/export-pdf-usager', name: 'signalement_gen_pdf')]
    public function generatePdfSignalement(
        string $code,
        MessageBusInterface $messageBus,
        SignalementRepository $signalementRepository,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted('SIGN_USAGER_VIEW', $signalement);
        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();
        if (null === $signalementUser->getEmail()) {
            $this->addFlash('error', 'Il n\'y a pas d\'adresse e-mail à laquelle vous envoyer le signalement au format PDF.');
        } else {
            $usagerEmail = $signalementUser->getEmail();

            $message = (new PdfExportMessage())
                ->setSignalementId($signalement->getId())
                ->setUserEmail($usagerEmail)
                ->setIsForUsager(true);

            $messageBus->dispatch($message);

            $this->addFlash(
                'success',
                \sprintf(
                    'Le signalement au format PDF vous sera envoyé par e-mail à l\'adresse suivante : %s. L\'envoi peut prendre plusieurs minutes. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                    $usagerEmail
                )
            );
        }

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }
}
