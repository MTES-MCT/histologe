<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Form\ConfigType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class BackConfigController extends AbstractController
{
    #[Route('/{id}/config', name: 'back_config', methods: ['GET', 'POST'])]
    public function config(Territory $territory, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('CONFIG_EDIT', $territory->getConfig());
        $title = 'Administration - Configration';
        $config = $territory->getConfig();
        $logo = $config->getLogotype();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($request->files->get('config')['logotype'])) {
                $logotype = $request->files->get('config')['logotype'];
                $originalFilename = pathinfo($logotype->getClientOriginalName(), \PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logotype->guessExtension();
                if ($newFilename && '' !== $newFilename) {
                    try {
                        $logotype->move(
                            $this->getParameter('images_dir'),
                            $newFilename
                        );
                        $config->setLogotype($newFilename);
                    } catch (UploadException $e) {
                        // TODO: Notif fail upload
                    }
                }
            } else {
                $config->setLogotype($logo);
            }
            $entityManager->persist($config);
            $entityManager->flush();
        }

        return $this->render('back/config/index.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'logotype' => $config->getLogotype(),
        ]);
    }
}
