<?php

namespace App\Controller\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Entity\ServiceSecoursRoute;
use App\Factory\SignalementFactory;
use App\Form\ServiceSecours\ServiceSecoursType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Flow\FormFlowInterface;
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
    #[Route('/services-secours/{slug:serviceSecoursRoute}/{uuid:serviceSecoursRoute}',
        name: 'service_secours_index',
        methods: ['GET', 'POST'])
    ]
    public function index(
        Request $request,
        ServiceSecoursRoute $serviceSecoursRoute,
        SignalementFactory $signalementFactory,
    ): Response {
        $serviceSecours = new FormServiceSecours();
        /** @var FormFlowInterface $flow */
        $flow = $this->createForm(ServiceSecoursType::class, $serviceSecours);
        $flow->handleRequest($request);
        if ($flow->isSubmitted() && $flow->isValid() && $flow->isFinished()) {
            $signalement = $signalementFactory->createInstanceFromFormServiceSecours($flow->getData(), $serviceSecoursRoute);

            // dump($signalement); // for testing purpose
            // TODO : persist and flush
            // voir les traitements fait dans App\Controller\Api\SignalementCreateController.php, création de la référence dans une transaction en particulier)
            return $this->render('service_secours/success.html.twig', ['serviceSecoursRoute' => $serviceSecoursRoute, 'signalement' => $signalement]);
        }

        return $this->render('service_secours/index.html.twig', [
            'form' => $flow->getStepForm(),
            'serviceSecoursRoute' => $serviceSecoursRoute,
        ]);
    }

    #[Route('/services-secours/{slug:serviceSecoursRoute}/{uuid:serviceSecoursRoute}/site.webmanifest',
        name: 'service_secours_webmanifest',
        methods: ['GET'])
    ]
    public function webmanifest(ServiceSecoursRoute $serviceSecoursRoute, Request $request): JsonResponse
    {
        $startUrl = $this->generateUrl('service_secours_index', [
            'slug' => $serviceSecoursRoute->getSlug(),
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
            'lang' => 'fr',
            'dir' => 'ltr',
            'orientation' => 'portrait',
            'description' => 'Signaler une situation de mal logement',
            'categories' => ['government', 'utilities'],
        ];

        $response = new JsonResponse($manifest);
        $response->headers->set('Content-Type', 'application/manifest+json');

        return $response;
    }

    #[Route(
        '/{any}',
        name: 'service_secours_fallback',
        requirements: ['any' => '(?!_wdt/|_profiler/|csp-report|signalement/handle).*'],
    )]
    public function fallback(): Response
    {
        throw $this->createNotFoundException();
    }
}
