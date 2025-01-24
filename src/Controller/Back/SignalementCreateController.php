<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/signalement')]
class SignalementCreateController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_BO_SIGNALEMENT_CREATE')]
        bool $featureSignalementCreate,
    ) {
        if (!$featureSignalementCreate) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/create', name: 'back_signalement_create', methods: 'GET')]
    public function createSignalement(
    ): Response {
        return $this->render('back/signalement_create/index.html.twig');
    }
}
