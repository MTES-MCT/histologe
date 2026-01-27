<?php

namespace App\Controller\ServiceSecours;

use App\Dto\Api\Request\SignalementRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\ServiceSecoursRoute;
use App\Form\ServiceSecoursType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(
    path : '/',
    requirements: ['domain' => '[^.]+(?:\.[^.]+)*'],
    host: 'service-secours.{domain}',
    priority: 100
)
]
class ServiceSecoursController extends AbstractController
{
    #[Route('/services-secours/{name:serviceSecoursRoute}/{uuid:serviceSecoursRoute}',
        name: 'service_secours_index',
        methods: ['GET', 'POST'])
    ]
    public function index(
        Request $request,
        ServiceSecoursRoute $serviceSecoursRoute,
    ): Response {
        $signalementRequest = new SignalementRequest();
        $signalementRequest->profilDeclarant = ProfileDeclarant::SERVICE_SECOURS->value;

        $form = $this->createForm(ServiceSecoursType::class, $signalementRequest);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // TODO : voir les traitements fait dans App\Controller\Api\SignalementCreateController.php pour les adapter / refactoriser ici
        }

        return $this->render('service_secours/index.html.twig', [
            'form' => $form,
            'serviceSecoursRoute' => $serviceSecoursRoute,
        ]);
    }

    #[Route('/services-secours/{name:serviceSecoursRoute}/{uuid:serviceSecoursRoute}/site.webmanifest',
        name: 'service_secours_webmanifest',
        methods: ['GET'])
    ]
    public function webmanifest(ServiceSecoursRoute $serviceSecoursRoute, Request $request): JsonResponse
    {
        $startUrl = $this->generateUrl('service_secours_index', [
            'name' => $serviceSecoursRoute->getName(),
            'uuid' => $serviceSecoursRoute->getUuid(),
            'domain' => $request->attributes->get('domain'),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        $manifest = [
            'name' => 'signal logement ('.$serviceSecoursRoute->getName().')',
            'short_name' => 'signal logement',
            'start_url' => $startUrl,
            'scope' => '/',
            'icons' => [
                [
                    'src' => '/service-secours/android-chrome-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/service-secours/android-chrome-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'orientation' => 'portrait',
        ];

        return new JsonResponse($manifest);
    }

    #[Route(
        '/{any}',
        name: 'service_secours_fallback',
        requirements: ['any' => '(?!_wdt/|_profiler/|csp-report).*'],
    )]
    public function fallback(): Response
    {
        throw $this->createNotFoundException();
    }
}
