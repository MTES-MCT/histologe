<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
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

        $this->addFlash(
            'success',
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
            // $documentType = DocumentType::tryFrom($request->get('document_type')) ?? DocumentType::AUTRE;
            $documentType = DocumentType::AUTRE;
            list($fileList, $descriptionList) = $signalementFileProcessor->process($files, $inputName, $documentType);

            if ($signalementFileProcessor->isValid()) {
                $nbFiles = \count($fileList);
                $description = (string) $nbFiles;
                $suivi = $suiviFactory->createInstanceFrom($this->getUser(), $signalement);
                // TODO : distinguer documents partenaires et documents sur la istuation usager
                // TODO : afficher la liste des désordres concernés pour l'ajout de photo
                if (FILE::INPUT_NAME_DOCUMENTS === $inputName) {
                    $description .= $nbFiles > 1 ? ' documents partenaires ont été ajoutés au signalement :'
                    : ' document partenaire a été ajouté au signalement :';
                } else {
                    $description .= $nbFiles > 1 ? ' photos ont été ajoutés au signalement :'
                    : ' photo a été ajouté au signalement :';
                }
                $suivi->setDescription(
                    $description
                    .'<ul>'
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
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))) {
            if ($uploadHandlerService->deleteSignalementFile($signalement, $type, $filename, $fileRepository)) {
                $suivi = $suiviFactory->createInstanceFrom($this->getUser(), $signalement);
                /** @var User $user */
                $user = $this->getUser();
                $description = $user->getNomComplet().' a supprimé le document suivant :';
                $suivi->setDescription(
                    $description
                    .'<ul>'
                    .$filename
                    .'</ul>'
                );
                $suivi->setType(SUIVI::TYPE_AUTO);

                $entityManager->persist($suivi);
                $entityManager->flush();

                return $this->json(['response' => 'success']);
            }
        }

        return $this->json(['response' => 'error'], 400);
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
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('FILE_EDIT', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_file_'.$signalement->getId(), $request->get('_token'))) {
            $file = $fileRepository->findOneBy(
                [
                    'id' => $request->get('file_id'),
                    'signalement' => $signalement,
                ]
            );
            if (null !== $file) {
                $documentType = DocumentType::tryFrom($request->get('documentType'));
                if (null !== $documentType) {
                    $file->setDocumentType($documentType);
                    if (DocumentType::SITUATION === $documentType) {
                        $desordreSlug = $request->get('desordreSlug');
                        if ($desordreSlug !== $file->getDesordreSlug()) {
                            $file->setDesordreSlug($desordreSlug);
                        }
                    } else {
                        if (null !== $file->getDesordreSlug()) {
                            $file->setDesordreSlug(null);
                        }
                    }

                    $entityManager->persist($file);
                    $entityManager->flush();

                    if ('document' === $file->getFileType()) {
                        $this->addFlash('success', 'Le document a bien été modifié.');
                    } else {
                        $this->addFlash('success', 'La photo a bien été modifiée.');
                    }
                } else {
                    $this->addFlash('error', 'Mauvais type de fichier');
                }
            } else {
                $this->addFlash('error', 'Ce fichier n\'existe plus');
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification...');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }
}
