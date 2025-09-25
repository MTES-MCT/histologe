<?php

namespace App\Controller\Back;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/cgu')]
class CguController extends AbstractController
{
    private const CGU_VERSIONS = [
        '24-07-2024' => 'archive_2024-07-24.html.twig',
        '20-01-2023' => 'archive_2023-01-20.html.twig',
        '12-08-2022' => 'archive_2022-08-12.html.twig',
    ];

    #[Route('/valider', name: 'cgu_bo_confirm', methods: 'POST')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
    ): Response {
        $decodedRequest = json_decode($request->getContent());
        if ($this->isCsrfTokenValid('cgu_bo_confirm', $decodedRequest->_token)) {
            $currentCguVersion = $parameterBag->get('cgu_current_version');
            /** @var User $user */
            $user = $this->getUser();
            $user->setCguVersionChecked($currentCguVersion);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], 400);
    }

    #[Route('/archives', name: 'back_cgu_bo_archives', methods: 'GET')]
    public function archives(ParameterBagInterface $parameterBag): Response
    {
        $versions = [];
        foreach (self::CGU_VERSIONS as $date => $template) {
            $versions[] = [
                'date' => $date,
                'description' => 'Version du '.str_replace('-', '/', $date),
            ];
        }

        return $this->render('back/cgu/archives.html.twig', [
            'versions' => $versions,
            'current_version' => $parameterBag->get('cgu_current_version'),
        ]);
    }

    #[Route('/archives/{date}', name: 'back_cgu_bo_archive_version', methods: 'GET')]
    public function archiveVersion(string $date, ParameterBagInterface $parameterBag): Response
    {
        if (!isset(self::CGU_VERSIONS[$date])) {
            throw $this->createNotFoundException('Version des CGU introuvable');
        }

        return $this->render('back/cgu/archive_version.html.twig', [
            'date' => str_replace('-', '/', $date),
            'template_file' => self::CGU_VERSIONS[$date],
            'current_version' => $parameterBag->get('cgu_current_version'),
        ]);
    }
}
