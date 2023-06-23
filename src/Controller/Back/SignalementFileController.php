<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use App\Repository\FileRepository;
use App\Service\Signalement\SignalementFileProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class SignalementFileController extends AbstractController
{
    public const INPUT_NAME_PHOTOS = 'photos';
    public const INPUT_NAME_DOCUMENTS = 'documents';

    #[Route('/{uuid}/pdf', name: 'back_signalement_gen_pdf')]
    public function generatePdfSignalement(
        Signalement $signalement,
        Pdf $knpSnappyPdf,
    ): Response {
        $criticitesArranged = [];
        foreach ($signalement->getCriticites() as $criticite) {
            $situationLabel = $criticite->getCritere()->getSituation()->getLabel();
            $critereLabel = $criticite->getCritere()->getLabel();
            $criticitesArranged[$situationLabel][$critereLabel] = $criticite;
        }

        $html = $this->renderView('pdf/signalement.html.twig', [
            'signalement' => $signalement,
            'situations' => $criticitesArranged,
        ]);
        $options = [
            'images' => true,
            'enable-local-file-access' => true,
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
        ];
        $knpSnappyPdf->setTimeout(120);

        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html, $options),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$signalement->getReference().'.pdf"',
            ]
        );
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
            $inputName = isset($files[self::INPUT_NAME_DOCUMENTS])
                ? self::INPUT_NAME_DOCUMENTS
                : self::INPUT_NAME_PHOTOS;

            list($fileList, $descriptionList) = $signalementFileProcessor->process($files, $inputName);

            if ($signalementFileProcessor->isValid()) {
                $suivi = $suiviFactory->createInstanceFrom($this->getUser(), $signalement);
                $suivi->setDescription('Ajout de '
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
                function (File $file) use ($fileStorage, $fileType, $filename) {
                    return $fileType === $file->getFileType()
                        && $filename === $file->getFilename()
                        && $fileStorage->fileExists($filename);
                }
            );

            if (!$fileCollection->isEmpty()) {
                $file = $fileCollection->current();
                $fileStorage->delete($file->getFilename());
                $fileRepository->remove($file, true);

                return $this->json(['response' => 'success']);
            }
        }

        return $this->json(['response' => 'error'], 400);
    }
}
