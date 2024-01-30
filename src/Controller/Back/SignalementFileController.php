<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\FileRepository;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
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
        UploadHandlerService $uploadHandlerService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))) {
            if ($uploadHandlerService->deleteSignalementFile($signalement, $type, $filename, $fileRepository)) {
                return $this->json(['response' => 'success']);
            }
        }

        return $this->json(['response' => 'error'], 400);
    }
}
