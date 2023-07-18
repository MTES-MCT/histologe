<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/nouveau-formulaire')]
class FrontNewSignalementController extends AbstractController
{
    #[Route('/signalement', name: 'front_nouveau_formulaire')]
    public function index(ParameterBagInterface $parameterBag): Response
    {
        if (!$parameterBag->get('feature_new_form')) {
            return $this->redirectToRoute('front_signalement');
        }

        return $this->render('front/nouveau_formulaire.html.twig');
    }
}
