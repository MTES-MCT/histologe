<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\FileRepository;
use App\Service\ImageManipulationService;
use App\Service\Signalement\SignalementFileProcessor;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

        if ($signalement->getPhotos()->count() < 21) {
            $message = (new PdfExportMessage())
                ->setSignalementId($signalement->getId())
                ->setUserEmail($user->getEmail());

            $messageBus->dispatch($message);

            $this->addFlash('success',
                sprintf(
                    'L\'export pdf vous sera envoyé par email à l\'adresse suivante : %s. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                    $user->getEmail()
                )
            );
        } else {
            $this->addFlash('error', 'La fonctionnalité est temporairement désactivée sur ce signalement en raison d\'un trop grand nombre de photos.');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/{uuid}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
        SignalementFileProcessor $signalementFileProcessor,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('FILE_CREATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), $request->get('_token'))
            && $files = $request->files->get('signalement-add-file')) {
            $inputName = isset($files[File::INPUT_NAME_DOCUMENTS])
                ? File::INPUT_NAME_DOCUMENTS
                : File::INPUT_NAME_PHOTOS;

            list($fileList, $descriptionList) = $signalementFileProcessor->process($files, $inputName);

            if ($signalementFileProcessor->isValid()) {
                $suivi = $suiviFactory->createInstanceFrom($this->getUser(), $signalement);
                $suivi->setDescription(
                    'Ajout de '
                    .$inputName
                    .' au signalement<ul>'
                    .implode('', $descriptionList)
                    .'</ul>'
                );
                $suivi->setType(SUIVI::TYPE_AUTO);
                $signalementFileProcessor->addFilesToSignalement($fileList, $signalement, $this->getUser());

                $entityManager->persist($suivi);
                $entityManager->persist($signalement);
                $entityManager->flush();
                $this->addFlash('success', 'Envoi de '.ucfirst($inputName).' effectué avec succès !');
            } else {
                foreach ($signalementFileProcessor->getErrors() as $errorMessage) {
                    $this->addFlash('error', $errorMessage);
                }
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenu lors du téléchargement');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/{uuid}/file/{type}/{filename}/delete', name: 'back_signalement_delete_file')]
    public function deleteFileSignalement(
        Signalement $signalement,
        string $type,
        string $filename,
        Request $request,
        FileRepository $fileRepository,
        FilesystemOperator $fileStorage
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))) {
            $fileType = 'documents' === $type ? File::FILE_TYPE_DOCUMENT : File::FILE_TYPE_PHOTO;

            $fileCollection = $signalement->getFiles()->filter(
                function (File $file) use ($fileType, $filename) {
                    return $fileType === $file->getFileType()
                        && $filename === $file->getFilename();
                }
            );

            if (!$fileCollection->isEmpty()) {
                $file = $fileCollection->current();
                if ($fileStorage->fileExists($file->getFilename())) {
                    $fileStorage->delete($file->getFilename());
                }
                $pathInfo = pathinfo($filename);
                $resize = $pathInfo['filename'].ImageManipulationService::SUFFIX_RESIZE.'.'.$pathInfo['extension'];
                $thumb = $pathInfo['filename'].ImageManipulationService::SUFFIX_THUMB.'.'.$pathInfo['extension'];
                if ($fileStorage->fileExists($resize)) {
                    $fileStorage->delete($resize);
                }
                if ($fileStorage->fileExists($thumb)) {
                    $fileStorage->delete($thumb);
                }

                $fileRepository->remove($file, true);

                return $this->json(['response' => 'success']);
            }
        }

        return $this->json(['response' => 'error'], 400);
    }
}
