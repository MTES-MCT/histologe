<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackDashboardController extends AbstractController
{
    public function __construct(
        ) {
    }

    #[Route('/', name: 'back_dashboard')]
    public function index(bool $featureDashboardEnable): Response
    {
        if (!$featureDashboardEnable) {
            return $this->redirectToRoute('back_index');
        }

        $title = 'Tableau de bord';

        return $this->render('back/dashboard/index.html.twig', [
            'title' => $title,
        ]);
    }
}
