<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\FileRepository;
use App\Repository\InterventionRepository;
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
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        /** @var User $user */
        $user = $this->getUser();

        $message = (new PdfExportMessage())
            ->setSignalementId($signalement->getId())
            ->setUserEmail($user->getEmail());

        $messageBus->dispatch($message);

        $this->addFlash(
            'success',
            \sprintf(
                'L\'export pdf vous sera envoyé par e-mail à l\'adresse suivante : %s. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                $user->getEmail()
            )
        );

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/{uuid:signalement}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SignalementFileProcessor $signalementFileProcessor,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        if (!$this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), $request->get('_token')) || !$files = $request->files->get('signalement-add-file')) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => 'Token CSRF invalide ou paramètre manquant, veuillez rechargez la page'], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Token CSRF invalide ou paramètre manquant, veuillez rechargez la page');

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        $inputName = isset($files[File::INPUT_NAME_DOCUMENTS])
            ? File::INPUT_NAME_DOCUMENTS
            : File::INPUT_NAME_PHOTOS;
        $documentType = DocumentType::AUTRE;
        if ($request->get('documentType') && $request->get('documentType') === DocumentType::AUTRE_PROCEDURE->name) {
            $documentType = DocumentType::AUTRE_PROCEDURE;
        }
        $fileList = $signalementFileProcessor->process($files, $inputName, $documentType);

        if (!$signalementFileProcessor->isValid()) {
            $errorMessages = $signalementFileProcessor->getErrorMessages();
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error error-raw', $errorMessages);

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        /** @var User $user */
        $user = $this->getUser();
        $signalementFileProcessor->addFilesToSignalement(
            fileList: $fileList,
            signalement: $signalement,
            user: $user,
            isWaitingSuivi: true
        );
        $entityManager->persist($signalement);
        $entityManager->flush();
        if ($request->isXmlHttpRequest()) {
            return $this->json(['response' => $signalementFileProcessor->getLastFile()->getId()]);
        }
        $this->addFlash('success', 'Envoi de '.ucfirst($inputName).' effectué avec succès !');

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/{uuid:signalement}/file-waiting-suivi', name: 'back_signalement_file_waiting_suivi')]
    public function fileWaitingSuiviSignalement(
        Signalement $signalement,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
        UploadHandlerService $uploadHandlerService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
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

        $suivi = $suiviFactory->createInstanceForFilesSignalement($user, $signalement, $files);
        $entityManager->persist($suivi);

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
        $this->addFlash('success', 'Les documents ont bien été ajoutés.');

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
        EntityManagerInterface $entityManager,
        SuiviManager $suiviManager,
    ): Response {
        $fileId = $request->get('file_id');
        $file = $fileRepository->findOneBy(
            [
                'id' => $fileId,
                'signalement' => $signalement,
            ]
        );
        $this->denyAccessUnlessGranted('FILE_DELETE', $file);
        if (null === $file) {
            $this->addFlash('error', 'Ce fichier n\'existe plus');
        } elseif ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))
        ) {
            $filename = $file->getFilename();
            $type = $file->getFileType();
            if ($uploadHandlerService->deleteFile($file)) {
                if (!$this->isGranted('ROLE_ADMIN')) {
                    /** @var User $user */
                    $user = $this->getUser();
                    $description = $user->getNomComplet().' a supprimé ';
                    $description .= File::FILE_TYPE_DOCUMENT === $type ? 'le document suivant :' : 'la photo suivante :';
                    $description .= '<ul><li>'.$filename.'</li></ul>';
                    $suiviManager->createSuivi(
                        user: $user,
                        signalement: $signalement,
                        description: $description,
                        type: Suivi::TYPE_AUTO,
                    );
                }
                if (File::FILE_TYPE_DOCUMENT === $type) {
                    $this->addFlash('success', 'Le document a bien été supprimé.');
                } else {
                    $this->addFlash('success', 'La photo a bien été supprimée.');
                }
            } else {
                $this->addFlash('error', 'Le fichier n\'a pas été supprimé.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide, veuillez rechargez la page');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/file/delete-tmp/{id}', name: 'back_signalement_delete_tmpfile', methods: ['DELETE'])]
    public function deleteTmpFile(
        File $file,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_DELETE', $file);
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
    ): Response {
        if (!$this->isCsrfTokenValid('signalement_edit_file_'.$signalement->getId(), $request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => 'Token CSRF invalide, veuillez rechargez la page'], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Token CSRF invalide, veuillez rechargez la page');

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        $file = $fileRepository->findOneBy(
            [
                'id' => $request->get('file_id'),
                'signalement' => $signalement,
            ]
        );
        if (null === $file) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => 'Document introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Ce fichier n\'existe plus');

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        $this->denyAccessUnlessGranted('FILE_EDIT', $file);
        $documentType = DocumentType::tryFrom($request->get('documentType'));
        if (null === $documentType) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => 'Type de document invalide'], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Mauvais type de fichier');

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        $file->setDocumentType($documentType);
        $desordreSlug = $request->get('desordreSlug');
        $file->setDesordreSlug($desordreSlug);
        $interventionId = $request->get('interventionId');
        if (null !== $interventionId) {
            $intervention = $interventionRepository->find($interventionId);
            if ($intervention?->getSignalement() === $file->getSignalement()) {
                $file->setIntervention($intervention);
            }
        }
        $description = $request->get('description');
        if (isset($description) && mb_strlen($description) > 255) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => 'La description ne doit pas dépasser 255 caractères'], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'La description ne doit pas dépasser 255 caractères');

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        if (null !== $description) {
            $file->setDescription($description);
        }
        $entityManager->persist($file);
        $entityManager->flush();
        if ($request->isXmlHttpRequest()) {
            return $this->json(['response' => 'success']);
        }
        if ('document' === $file->getFileType()) {
            $this->addFlash('success', 'Le document a bien été modifié.');
        } else {
            $this->addFlash('success', 'La photo a bien été modifiée.');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }
}
