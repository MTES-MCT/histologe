<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\FileRepository;
use App\Repository\InterventionRepository;
use App\Security\Voter\FileVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\ImageManipulationHandler;
use App\Service\MessageHelper;
use App\Service\RequestDataExtractor;
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

#[Route('/bo/signalements')]
class SignalementFileController extends AbstractController
{
    #[Route('/{uuid:signalement}/pdf', name: 'back_signalement_gen_pdf')]
    public function generatePdfSignalement(
        Signalement $signalement,
        MessageBusInterface $messageBus,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VIEW, $signalement);
        /** @var User $user */
        $user = $this->getUser();

        $message = (new PdfExportMessage())
            ->setSignalementId($signalement->getId())
            ->setUserEmail($user->getEmail())
            ->setIsForUsager();

        $messageBus->dispatch($message);

        $message = \sprintf(
            'L\'export PDF a bien été envoyé par e-mail à l\'adresse suivante : %s. N\'oubliez pas de consulter vos courriers indésirables (spam) !',
            $user->getEmail()
        );
        $flashMessages[] = ['type' => 'success', 'title' => 'Export envoyé', 'message' => $message];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
    }

    /**
     * @throws \Throwable
     */
    #[Route('/{uuid:signalement}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SignalementFileProcessor $signalementFileProcessor,
    ): Response {
        if (!$this->isGranted(SignalementVoter::SIGN_EDIT_DRAFT, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_ACTIVE, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_CLOSED, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_INJONCTION, $signalement)) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), (string) $request->request->get('_token')) || !$files = $request->files->get('signalement-add-file')) {
            return $this->json(['response' => 'Token CSRF invalide ou paramètre manquant, veuillez recharger la page'], Response::HTTP_BAD_REQUEST);
        }
        $documentType = DocumentType::AUTRE;
        if ($request->request->get('documentType') && $request->request->get('documentType') === DocumentType::AUTRE_PROCEDURE->name) {
            $documentType = DocumentType::AUTRE_PROCEDURE;
        }
        $fileList = $signalementFileProcessor->process($files, $documentType);

        if (!$signalementFileProcessor->isValid()) {
            return $this->json(['response' => $signalementFileProcessor->getErrorMessages()], Response::HTTP_BAD_REQUEST);
        }
        /** @var User $user */
        $user = $this->getUser();
        $signalementFileProcessor->addFilesToSignalement(
            fileList: $fileList,
            signalement: $signalement,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
            user: $user,
            isWaitingSuivi: true
        );
        $entityManager->persist($signalement);
        $entityManager->flush();

        return $this->json(['response' => $signalementFileProcessor->getLastFile()->getId()]);
    }

    #[Route('/{uuid:signalement}/file-waiting-suivi', name: 'back_signalement_file_waiting_suivi')]
    public function fileWaitingSuiviSignalement(
        Signalement $signalement,
        EntityManagerInterface $entityManager,
        SuiviManager $suiviManager,
        UploadHandlerService $uploadHandlerService,
    ): JsonResponse {
        if (!$this->isGranted(SignalementVoter::SIGN_EDIT_DRAFT, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_ACTIVE, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_CLOSED, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_INJONCTION, $signalement)) {
            throw $this->createAccessDeniedException();
        }
        /** @var FileRepository $fileRepository */
        $fileRepository = $entityManager->getRepository(File::class);
        /** @var User $user */
        $user = $this->getUser();
        $files = $fileRepository->findBy(['signalement' => $signalement, 'isWaitingSuivi' => true, 'uploadedBy' => $user]);
        foreach ($files as $key => $file) {
            if ($uploadHandlerService->deleteIfExpiredFile($file)) {
                unset($files[$key]);
            }
        }
        if (!\count($files)) {
            return $this->json(['success' => true]);
        }
        $subscriptionCreated = false;
        if (SignalementStatus::CLOSED !== $signalement->getStatut()) {
            $suivi = $suiviManager->createInstanceForFilesSignalement(
                user: $user,
                signalement: $signalement,
                files: $files,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
                subscriptionCreated: $subscriptionCreated
            );
            $entityManager->persist($suivi);
        }

        $update = $entityManager->createQueryBuilder()
            ->update(File::class, 'f')
            ->set('f.isWaitingSuivi', 'false')
            ->where('f.signalement = :signalement')
            ->andWhere('f.isWaitingSuivi = true')
            ->andWhere('f.uploadedBy = :user')
            ->setParameter('signalement', $signalement)
            ->setParameter('user', $user)
            ->getQuery();
        $update->execute();

        $entityManager->flush();

        if (SignalementStatus::DRAFT !== $signalement->getStatut()) {
            $this->addFlash('success', ['title' => 'Documents ajoutés', 'message' => 'Les documents ont bien été ajoutés.']);
            if ($subscriptionCreated) {
                $this->addFlash('success', ['title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED]);
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/{uuid:signalement}/file/delete', name: 'back_signalement_delete_file')]
    public function deleteFileSignalement(
        Signalement $signalement,
        Request $request,
        FileRepository $fileRepository,
        UploadHandlerService $uploadHandlerService,
        SuiviManager $suiviManager,
    ): Response {
        $fileId = $request->request->get('file_id');
        $file = $fileRepository->findOneBy(['id' => $fileId, 'signalement' => $signalement]);
        $this->denyAccessUnlessGranted(FileVoter::FILE_DELETE, $file);
        $fragment = in_array($request->request->get('hash_src'), ['activite', 'situation']) ? $request->request->get('hash_src') : 'documents';
        if (!$this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), (string) $request->request->get('_token'))) {
            $message = MessageHelper::ERROR_MESSAGE_CSRF;
            if ('1' === $request->request->get('is_draft')) {
                return $this->json(['message' => $message], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', $message);

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid(), '_fragment' => $fragment]));
        }
        $filename = $file->getFilename();
        if (!$uploadHandlerService->deleteFile($file)) {
            $message = 'Le fichier n\'a pas été supprimé';
            if ('1' === $request->request->get('is_draft')) {
                return $this->json(['message' => $message], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', ['title' => 'Erreur de suppression', 'message' => $message]);
        }
        $subscriptionCreated = false;
        if (!$this->isGranted('ROLE_ADMIN')
            && in_array($signalement->getStatut(), [SignalementStatus::CLOSED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED])
        ) {
            /** @var User $user */
            $user = $this->getUser();
            $description = $user->getNomComplet().' a supprimé le document suivant : <ul><li>'.$filename.'</li></ul>';
            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::DOCUMENT_DELETED_BY_PARTNER,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
                user: $user,
                subscriptionCreated: $subscriptionCreated,
            );
        }
        if ('1' === $request->request->get('is_draft')) {
            return $this->json(['success' => true]);
        }
        $this->addFlash('success', ['title' => 'Document supprimé', 'message' => 'Le document a bien été supprimé.']);
        if ($subscriptionCreated) {
            $this->addFlash('success', ['title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED]);
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid(), '_fragment' => $fragment]));
    }

    #[Route('/file/delete-tmp/{id}', name: 'back_signalement_delete_tmpfile', methods: ['DELETE'])]
    public function deleteTmpFile(
        File $file,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(FileVoter::FILE_DELETE, $file);
        if (!$file->isIsWaitingSuivi()) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }
        if (!$uploadHandlerService->deleteFile($file)) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }
        $entityManager->remove($file);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/{uuid:signalement}/file/edit', name: 'back_signalement_edit_file')]
    public function editFileSignalement(
        Signalement $signalement,
        Request $request,
        FileRepository $fileRepository,
        EntityManagerInterface $entityManager,
        InterventionRepository $interventionRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): Response {
        $requestData = $request->request->all();
        $token = RequestDataExtractor::getString($requestData, '_token');
        if (!$this->isCsrfTokenValid('signalement_edit_file_'.$signalement->getId(), $token)) {
            $errorMsg = 'Token CSRF invalide, veuillez recharger la page';

            return $this->json(['response' => $errorMsg, 'errors' => ['custom' => ['errors' => [$errorMsg]]]], Response::HTTP_BAD_REQUEST);
        }
        $fileId = RequestDataExtractor::getString($requestData, 'file_id');
        $file = $fileRepository->findOneBy(['id' => $fileId, 'signalement' => $signalement]);
        if (null === $file || ($file->getIntervention() && DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType())) {
            $errorMsg = 'Document introuvable';

            return $this->json(['response' => $errorMsg, 'errors' => ['custom' => ['errors' => [$errorMsg]]]], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted(FileVoter::FILE_EDIT, $file);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $documentTypeData = RequestDataExtractor::getString($requestData, 'documentType');
        $documentType = DocumentType::tryFrom($documentTypeData);
        if (DocumentType::PHOTO_VISITE === $file->getDocumentType()) {
            // un document typé PHOTO_VISITE ne peut pas changer de type
        } elseif ($documentTypeData && isset($infoDesordres['criteres'][$documentTypeData])) {
            $file->setDocumentType(DocumentType::PHOTO_SITUATION);
            $file->setDesordreSlug($documentTypeData);
        } elseif (null === $documentType) {
            $errorMsg = 'Type de document invalide';

            return $this->json(['response' => $errorMsg, 'errors' => ['custom' => ['errors' => [$errorMsg]]]], Response::HTTP_BAD_REQUEST);
        } else {
            $file->setDocumentType($documentType);
            $file->setDesordreSlug(null);
        }
        $interventionId = RequestDataExtractor::getString($requestData, 'interventionId');
        if (null !== $interventionId && DocumentType::PHOTO_VISITE === $documentType) {
            $intervention = $interventionRepository->find($interventionId);
            if ($intervention?->getSignalement() === $file->getSignalement()) {
                $file->setIntervention($intervention);
            }
        }
        $description = RequestDataExtractor::getString($requestData, 'description');
        if ($file->isTypeImage()) {
            if ($description && mb_strlen($description) > 255) {
                $errorMsg = 'La description ne doit pas dépasser 255 caractères';

                return $this->json(['response' => $errorMsg, 'errors' => ['custom' => ['errors' => [$errorMsg]]]], Response::HTTP_BAD_REQUEST);
            }
            $file->setDescription($description);
        } else {
            $file->setDescription(null);
        }
        $entityManager->persist($file);
        $entityManager->flush();

        if ('edit' === $request->request->get('from')) {
            $this->addFlash('success', ['title' => 'Document modifié', 'message' => 'Le document a bien été modifié.']);
        }

        return $this->json(['response' => 'success']);
    }

    #[Route('/{uuid:signalement}/file/{id:file}/rotation', name: 'back_signalement_file_rotate', methods: ['POST'])]
    public function rotateFile(
        Signalement $signalement,
        File $file,
        Request $request,
        ImageManipulationHandler $imageManipulationHandler,
    ): Response {
        if (!$this->isGranted(SignalementVoter::SIGN_EDIT_ACTIVE, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_CLOSED, $signalement)
            && !$this->isGranted(SignalementVoter::SIGN_EDIT_INJONCTION, $signalement)) {
            throw $this->createAccessDeniedException();
        }
        $rotate = (int) $request->request->get('rotate', 0);
        if (!$rotate) {
            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid(), '_fragment' => 'documents']));
        }
        if (!$this->isCsrfTokenValid('save_file_rotation', (string) $request->request->get('_token'))) {
            $this->addFlash('error', MessageHelper::ERROR_MESSAGE_CSRF);

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid(), '_fragment' => 'documents']));
        }
        $angle = $rotate * 90 * -1;
        $imageManipulationHandler->rotate($file, $angle);
        $this->addFlash('success', ['title' => 'Modifications enregistrées', 'message' => 'La photo a bien été modifiée.']);

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid(), '_fragment' => 'documents']));
    }
}
