<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Entity\Suivi;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Snappy\Pdf;
use PHPUnit\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo/s')]
class BackSignalementFileController extends AbstractController
{

    #[Route('/{uuid}/pdf', name: 'back_signalement_gen_pdf')]
    public function generatePdfSignalement(Signalement $signalement, Pdf $knpSnappyPdf, EntityManagerInterface $entityManager)
    {
        $criticitesArranged = [];
        foreach ($signalement->getCriticites() as $criticite) {
            $criticitesArranged[$criticite->getCritere()->getSituation()->getLabel()][$criticite->getCritere()->getLabel()] = $criticite;
        }
        $html = $this->renderView('pdf/signalement.html.twig', [
            'signalement' => $signalement,
            'situations' => $criticitesArranged
        ]);
        $options = [
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
        ];

        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html, $options),
            200,
            array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $signalement->getReference() . '.pdf"'
            )
        );
    }

    #[Route('/{uuid}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): RedirectResponse
    {
        $this->denyAccessUnlessGranted('FILE_CREATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_add_file_' . $signalement->getId(), $request->get('_token')) && $files = $request->files->get('signalement-add-file')) {
            if (isset($files['documents']))
                $type = 'documents';
            if (isset($files['photos']))
                $type = 'photos';
            $setMethod = 'set' . ucfirst($type);
            $getMethod = 'get' . ucfirst($type);
            $list = [];
            $$type = $signalement->$getMethod();
            foreach ($files[$type] as $file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $titre = $originalFilename . '.' . $file->guessExtension();
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('uploads_dir'),
                        $newFilename
                    );
                } catch (Exception $e) {
                    dd($e);
                }
                $list[] = '<li><a class="fr-link" target="_blank" href="' . $this->generateUrl('show_uploaded_file', ['folder' => '_up', 'file' => $newFilename]) . '">' . $titre . '</a></li>';
                array_push($$type, [
                    'file' => $newFilename,
                    'titre' => $titre,
                    'user' => $this->getUser()->getId(),
                    'username' => $this->getUser()->getNomComplet(),
                    'date' => (new DateTimeImmutable())->format('d.m.Y')
                ]);
            }
            $suivi = new Suivi();
            $suivi->setCreatedBy($this->getUser());
            $suivi->setDescription('Ajout de ' . $type . ' au signalement<ul>' . implode("", $list) . '</ul>');
            $suivi->setSignalement($signalement);
            $signalement->$setMethod($$type);
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Envoi de ' . ucfirst($type) . ' effectué avec succès !');
        } else
            $this->addFlash('error', "Une erreur est survenu lors du téléchargement");
        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]) . '#documents');
    }

    #[Route('/{uuid}/file/{type}/{file}/delete', name: 'back_signalement_delete_file')]
    public function deleteFileSignalement(Signalement $signalement, $type, $file, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('FILE_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_file_' . $signalement->getId(), $request->get('_token'))) {
            $setMethod = 'set' . ucfirst($type);
            $getMethod = 'get' . ucfirst($type);
            $$type = $signalement->$getMethod();
            foreach ($$type as $k => $v) {
                if ($file === $v['file']) {
                    if (file_exists($this->getParameter('uploads_dir') . $file))
                        unlink($this->getParameter('uploads_dir') . $file);
                    unset($$type[$k]);
                }
            }
            $signalement->$setMethod($$type);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            return $this->json(['response' => 'success']);
        } else
            return $this->json(['response' => 'error'], 400);
    }

}