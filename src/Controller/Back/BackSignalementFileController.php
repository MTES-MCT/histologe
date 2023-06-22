<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Factory\FileFactory;
use App\Service\Files\FilenameGenerator;
use App\Service\Files\HeicToJpegConverter;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Snappy\Pdf;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class BackSignalementFileController extends AbstractController
{
    public const INPUT_NAME_PHOTOS = 'photos';
    public const INPUT_NAME_DOCUMENTS = 'documents';

    #[Route('/{uuid}/pdf', name: 'back_signalement_gen_pdf')]
    public function generatePdfSignalement(
        Signalement $signalement,
        Pdf $knpSnappyPdf,
    ) {
        $criticitesArranged = [];
        foreach ($signalement->getCriticites() as $criticite) {
            $criticitesArranged[$criticite->getCritere()->getSituation()->getLabel()][$criticite->getCritere()->getLabel()] = $criticite;
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
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        UploadHandlerService $uploadHandler,
        FileFactory $fileFactory,
        FilenameGenerator $filenameGenerator,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('FILE_CREATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), $request->get('_token'))
            && $files = $request->files->get('signalement-add-file')) {
            $key = isset($files[self::INPUT_NAME_DOCUMENTS]) ? self::INPUT_NAME_DOCUMENTS : self::INPUT_NAME_PHOTOS;
            $fileList = $list = [];

            /** @var UploadedFile $file */
            foreach ($files[$key] as $file) {
                if (\in_array($file->getMimeType(), HeicToJpegConverter::HEIC_FORMAT)) {
                    $message = <<<ERROR
                    Les fichiers de format HEIC/HEIF ne sont pas pris en charge,
                    merci de convertir votre image en JPEG ou en PNG avant de l'envoyer.
                    ERROR;
                    $logger->error($message);
                    $this->addFlash('error', $message);
                } else {
                    $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
                    $titre = $originalFilename.'.'.$file->guessExtension();
                    $newFilename = $filenameGenerator->generateSafeName($file);
                    try {
                        $newFilename = $uploadHandler->uploadFromFile($file, $filenameGenerator->generate($file));
                    } catch (MaxUploadSizeExceededException $exception) {
                        $newFilename = '';
                        $logger->error($exception->getMessage());
                        $this->addFlash('error', $exception->getMessage());
                    }
                    if (!empty($newFilename)) {
                        $list[] = '<li><a class="fr-link" target="_blank" href="'.$this->generateUrl(
                            'show_uploaded_file',
                            ['folder' => '_up', 'filename' => $newFilename]
                        ).'">'.$title = $filenameGenerator->getTitle().'</a></li>';
                        $fileList[] = [
                            'file' => $newFilename,
                            'title' => $title,
                            'user' => $this->getUser(),
                            'date' => new \DateTimeImmutable(),
                            'type' => 'documents' === $key ? File::FILE_TYPE_DOCUMENT : File::FILE_TYPE_PHOTO,
                        ];
                    }
                }
            }

            if (!empty($list)) {
                $suivi = new Suivi();
                $suivi->setCreatedBy($this->getUser());
                $suivi->setDescription('Ajout de '.$key.' au signalement<ul>'.implode('', $list).'</ul>');
                $suivi->setSignalement($signalement);
                $suivi->setType(SUIVI::TYPE_AUTO);

                foreach ($fileList as $fileItem) {
                    $file = $fileFactory->createInstanceFrom(
                        filename: $fileItem['file'],
                        title: $fileItem['title'],
                        type: $fileItem['type'],
                    );
                    $signalement->addFile($file);
                }

                $entityManager->persist($suivi);
                $entityManager->persist($signalement);
                $entityManager->flush();
                $this->addFlash('success', 'Envoi de '.ucfirst($key).' effectué avec succès !');
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenu lors du téléchargement');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
    }

    #[Route('/{uuid}/file/{type}/{filename}/delete', name: 'back_signalement_delete_file')]
    public function deleteFileSignalement(
        Signalement $signalement,
        string $type,
        string $filename,
        Request $request,
        ManagerRegistry $doctrine,
        FilesystemOperator $fileStorage
    ): JsonResponse {
        $this->denyAccessUnlessGranted('FILE_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_file_'.$signalement->getId(), $request->get('_token'))) {
            $setMethod = 'set'.ucfirst($type);
            $getMethod = 'get'.ucfirst($type);
            $type_list = $signalement->$getMethod();
            foreach ($type_list as $k => $v) {
                if ($filename === $v['file']) {
                    if ($fileStorage->fileExists($filename)) {
                        $fileStorage->delete($filename);
                    }
                    unset($type_list[$k]);
                }
            }
            $signalement->$setMethod($type_list);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], 400);
    }
}
