<?php

namespace App\Controller\ServiceSecours;

use App\Dto\Api\Request\SignalementRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Form\ServiceSecoursType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path : '/',
    requirements: ['domain' => '[^.]+(?:\.[^.]+)*'],
    host: 'service-secours.{domain}', )
]
class ServiceSecoursController extends AbstractController
{
    #[Route('/',
        name: 'service_secours_index',
        methods: ['GET', 'POST'],
        priority: 100)
    ]
    public function index(Request $request): Response
    {
        $signalementRequest = new SignalementRequest();
        $signalementRequest->profilDeclarant = ProfileDeclarant::SERVICE_SECOURS->value;

        $form = $this->createForm(ServiceSecoursType::class, $signalementRequest);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // TODO : voir les traitements fait dans App\Controller\Api\SignalementCreateController.php pour les adapter / refactoriser ici
        }

        return $this->render('service_secours/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/{any}',
        name: 'service_secours_fallback',
        requirements: ['any' => '.*'],
        priority: 50
    )]
    public function fallback(): Response
    {
        throw $this->createNotFoundException();
    }
}
