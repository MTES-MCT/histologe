<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class SitemapController extends AbstractController
{
    #[Route('/plan-du-site', name: 'back_plan_du_site')]
    public function index(): Response
    {
        return $this->render('back/sitemap/index.html.twig');
    }
}
