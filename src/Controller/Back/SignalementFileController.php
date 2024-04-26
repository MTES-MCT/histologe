<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Manager\FileManager;
use App\Manager\SuiviManager;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\FileRepository;
use App\Repository\InterventionRepository;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class SignalementFileController extends AbstractController
{
    #[Route('/{uuid}/pdf', name: 'back_signalement_gen_pdf')]
    public function generatePdfSignalement(
        Signalement $signalement,
        MessageBusInterface $messageBus
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $message = (new PdfExportMessage())
            ->setSignalementId($signalement->getId())
            ->setUserEmail($user->getEmail());

        $messageBus->dispatch($message);

        $this->addFlash(
            'success',
            sprintf(
                'L\'export pdf vous sera envoyé par e-mail à l\'adresse suivante : %s. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                $user->getEmail()
            )
        );

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/{uuid}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SuiviManager $suiviManager,
        SignalementFileProcessor $signalementFileProcessor,
        #[Autowire(env: 'FEATURE_DOCUMENTS_ENABLE')]
        bool $featureDocumentsEnable
    ): Response {
        $this->denyAccessUnlessGranted('FILE_CREATE', $signalement);
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
        list($fileList) = $signalementFileProcessor->process($files, $inputName, $documentType);

        if (!$signalementFileProcessor->isValid()) {
            $errorMessages = '';
            foreach ($signalementFileProcessor->getErrors() as $errorMessage) {
                $errorMessages .= $errorMessage.'<br>';
            }
            if ($request->isXmlHttpRequest()) {
                return $this->json(['response' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error error-raw', $errorMessages);

            return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
        }
        if ($featureDocumentsEnable) {
            $signalementFileProcessor->addFilesToSignalement(
                fileList: $fileList,
                signalement: $signalement,
                user: $this->getUser(),
                isWaitingSuivi: true
            );
        } else {
            $filesList = $signalementFileProcessor->addFilesToSignalement($fileList, $signalement, $this->getUser());
            $suivi = $suiviManager->createInstanceForFilesSignalement($this->getUser(), $signalement, $filesList);
            $entityManager->persist($suivi);
        }
        $entityManager->persist($signalement);
        $entityManager->flush();
        if ($request->isXmlHttpRequest()) {
            return $this->json(['response' => $signalementFileProcessor->getLastFile()->getId()]);
        }
        $this->addFlash('success', 'Envoi de '.ucfirst($inputName).' effectué avec succès !');

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/{uuid}/file-waiting-suivi', name: 'back_signalement_file_waiting_suivi')]
    public function fileWaitingSuiviSignalement(
        Signalement $signalement,
        EntityManagerInterface $entityManager,
        SuiviManager $suiviManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_CREATE', $signalement);
        $fileRepository = $entityManager->getRepository(File::class);
        $files = $fileRepository->findBy(['signalement' => $signalement, 'isWaitingSuivi' => true]);
        if (!\count($files)) {
            return $this->json(['success' => true]);
        }

        $suivi = $suiviManager->createInstanceForFilesSignalement($this->getUser(), $signalement, $files);
        $entityManager->persist($suivi);

        $update = $entityManager->createQueryBuilder()
            ->update(File::class, 'f')
            ->set('f.isWaitingSuivi', 'false')
            ->where('f.signalement = :signalement')
            ->andWhere('f.isWaitingSuivi = true')
            ->setParameter('signalement', $signalement)
            ->getQuery();
        $update->execute();

        $entityManager->flush();
        $this->addFlash('success', 'Les documents ont bien été ajoutés.');

        return $this->json(['success' => true]);
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/{uuid}/file/delete', name: 'back_signalement_delete_file')]
    public function deleteFileSignalement(
        Signalement $signalement,
        Request $request,
        FileRepository $fileRepository,
        UploadHandlerService $uploadHandlerService,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
        FileManager $fileManager,
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
            if ($uploadHandlerService->deleteSignalementFile($file, $fileRepository)) {
                $suivi = $suiviFactory->createInstanceFrom($this->getUser(), $signalement);
                /** @var User $user */
                $user = $this->getUser();
                $description = $user->getNomComplet().' a supprimé ';
                $description .= File::FILE_TYPE_DOCUMENT === $type ? 'le document suivant :' : 'la photo suivante :';
                $suivi->setDescription(
                    $description
                    .'<ul><li>'
                    .$filename
                    .'</li></ul>'
                );
                $suivi->setType(SUIVI::TYPE_AUTO);

                $entityManager->persist($suivi);
                $entityManager->flush();

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
        FileRepository $fileRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_DELETE', $file);
        if (!$file->isIsWaitingSuivi()) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }
        if (!$uploadHandlerService->deleteSignalementFile($file, $fileRepository)) {
            return $this->json(['success' => false], Response::HTTP_BAD_REQUEST);
        }
        $entityManager->remove($file);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/{uuid}/file/edit', name: 'back_signalement_edit_file')]
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
