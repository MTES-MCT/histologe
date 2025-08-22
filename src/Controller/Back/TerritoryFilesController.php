<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/espace-documentaire')]
class TerritoryFilesController extends AbstractController
{
    #[Route('/', name: 'back_territory_files_index', methods: ['GET'])]
    public function index(
    ): Response {
        return $this->render('back/territory-files/index.html.twig');
    }
}
