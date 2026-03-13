<?php

namespace App\Controller\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Signalement;
use App\Factory\SignalementFactory;
use App\Form\ServiceSecours\ServiceSecoursType;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\Export\ServiceSecoursPdfGenerator;
use App\Service\Signalement\ReferenceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
        SignalementRepository $signalementRepository,
        EntityManagerInterface $entityManager,
        ReferenceGenerator $referenceGenerator,
        UserManager $userManager,
        AutoAssigner $autoAssigner,
        NotificationMailerRegistry $notificationMailerRegistry,
        ServiceSecoursPdfGenerator $serviceSecoursPdfGenerator,
    ): Response {
        $serviceSecours = new FormServiceSecours();
        /** @var FormFlowInterface $flow */
        $flow = $this->createForm(ServiceSecoursType::class, $serviceSecours);
        $flow->handleRequest($request);
        if ($flow->isSubmitted() && $flow->isValid() && $flow->isFinished()) {
            $signalement = $signalementFactory->createInstanceFromFormServiceSecours($flow->getData(), $serviceSecoursRoute);

            // dump($signalement); // for testing purpose

            $entityManager->beginTransaction();
            $signalement->setReference($referenceGenerator->generateReference($signalement->getTerritory()));
            $signalementRepository->save($signalement, true);
            $entityManager->commit();
            $userManager->createUsagersFromSignalement($signalement);
            $autoAssigner->assignOrSendNewSignalementNotification($signalement);
            // acusé de reception
            $pdfContent = $serviceSecoursPdfGenerator->generate($signalement);
            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_SERVICE_SECOURS_ACCUSE_RECEPTION,
                    to: $serviceSecoursRoute->getEmail(),
                    signalement: $signalement,
                    attachment: $pdfContent,
                )
            );

            return $this->render('service_secours/success.html.twig', ['serviceSecoursRoute' => $serviceSecoursRoute, 'signalement' => $signalement]);
        }

        return $this->render('service_secours/index.html.twig', [
            'form' => $flow->getStepForm(),
            'serviceSecoursRoute' => $serviceSecoursRoute,
        ]);
    }

    #[Route(
        '/services-secours/{slug:serviceSecoursRoute}/{uuid:serviceSecoursRoute}/pdf/{uuidSignalement}',
        name: 'service_secours_pdf',
        methods: 'GET',
        defaults: ['_signed' => true]
    )]
    public function pdf(
        ServiceSecoursRoute $serviceSecoursRoute,
        #[MapEntity(mapping: ['uuidSignalement' => 'uuid'])] Signalement $signalement,
        ServiceSecoursPdfGenerator $serviceSecoursPdfGenerator,
    ): Response {
        if ($signalement->getServiceSecours() !== $serviceSecoursRoute) {
            throw $this->createNotFoundException();
        }
        // dump($signalement); // for testing purpose
        $pdfContent = $serviceSecoursPdfGenerator->generate($signalement);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="signalement-'.$signalement->getReference().'.pdf"');

        return $response;
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
        requirements: ['any' => '(?!_wdt/|_profiler/|csp-report).*'],
    )]
    public function fallback(): Response
    {
        throw $this->createNotFoundException();
    }
}
