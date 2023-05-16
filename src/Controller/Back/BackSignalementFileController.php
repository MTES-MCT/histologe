<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Service\Files\HeicToJpegConverter;
use App\Service\UploadHandlerService;
use DateTimeImmutable;
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
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo/signalements')]
class BackSignalementFileController extends AbstractController
{
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
        ManagerRegistry $doctrine,
        SluggerInterface $slugger,
        LoggerInterface $logger,
        UploadHandlerService $uploadHandler
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('FILE_CREATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_add_file_'.$signalement->getId(), $request->get('_token'))
            && $files = $request->files->get('signalement-add-file')) {
            $type = '';
            if (isset($files['documents'])) {
                $type = 'documents';
            }
            if (isset($files['photos'])) {
                $type = 'photos';
            }
            $setMethod = 'set'.ucfirst($type);
            $getMethod = 'get'.ucfirst($type);
            $list = [];
            $type_list = $signalement->$getMethod();

            /** @var UploadedFile $file */
            foreach ($files[$type] as $file) {
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
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                    try {
                        $newFilename = $uploadHandler->uploadFromFile($file, $newFilename);
                    } catch (MaxUploadSizeExceededException $exception) {
                        $newFilename = '';
                        $logger->error($exception->getMessage());
                        $this->addFlash('error', $exception->getMessage());
                    }
                    if (!empty($newFilename)) {
                        $list[] = '<li><a class="fr-link" target="_blank" href="'.$this->generateUrl(
                            'show_uploaded_file',
                            ['folder' => '_up', 'filename' => $newFilename]
                        ).'">'.$titre.'</a></li>';
                        if (null === $type_list) {
                            $type_list = [];
                        }
                        $type_list[] = [
                            'file' => $newFilename,
                            'titre' => $titre,
                            'user' => $this->getUser()->getId(),
                            'username' => $this->getUser()->getNomComplet(),
                            'date' => (new DateTimeImmutable())->format('d.m.Y'),
                        ];
                    }
                }
            }

            if (!empty($list)) {
                $suivi = new Suivi();
                $suivi->setCreatedBy($this->getUser());
                $suivi->setDescription('Ajout de '.$type.' au signalement<ul>'.implode('', $list).'</ul>');
                $suivi->setSignalement($signalement);
                $suivi->setType(SUIVI::TYPE_AUTO);
                $signalement->$setMethod($type_list);
                $doctrine->getManager()->persist($suivi);
                $doctrine->getManager()->persist($signalement);
                $doctrine->getManager()->flush();
                $this->addFlash('success', 'Envoi de '.ucfirst($type).' effectué avec succès !');
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
