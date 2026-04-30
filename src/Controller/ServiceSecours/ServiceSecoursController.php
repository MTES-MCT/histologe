<?php

namespace App\Controller\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Entity\Enum\AppContext;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Signalement;
use App\Factory\SignalementServiceSecoursFactory;
use App\Form\ServiceSecours\ServiceSecoursType;
use App\Manager\SignalementManager;
use App\Manager\UserManager;
use App\Messenger\Message\SignalementServiceSecoursFileMessage;
use App\Repository\DesordreCritereRepository;
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
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(
    path : '/',
    requirements: ['domain' => '[^.]+(?:\.[^.]+)*'],
    host: 'service-secours.{domain}',
    priority: 100
)]
class ServiceSecoursController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route('/services-secours/{slug:serviceSecoursRoute}/{uuid:serviceSecoursRoute}',
        name: 'service_secours_index',
        methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ServiceSecoursRoute $serviceSecoursRoute,
        SignalementServiceSecoursFactory $signalementServiceSecoursFactory,
        DesordreCritereRepository $desordreCritereRepository,
        SignalementManager $signalementManager,
        EntityManagerInterface $entityManager,
        ReferenceGenerator $referenceGenerator,
        UserManager $userManager,
        AutoAssigner $autoAssigner,
        NotificationMailerRegistry $notificationMailerRegistry,
        ServiceSecoursPdfGenerator $serviceSecoursPdfGenerator,
        MessageBusInterface $messageBus,
    ): Response {
        $serviceSecours = new FormServiceSecours();
        /** @var FormFlowInterface $flow */
        $flow = $this->createForm(ServiceSecoursType::class, $serviceSecours);
        $flow->handleRequest($request);
        if ($flow->isSubmitted() && $flow->isValid()) {
            $data = $flow->getData();
            if ('step2' === $data->currentStep && 'appartement' !== $data->step2->natureLogement) {
                $data->step5->autresOccupantsDesordre = null;
            }
        }
        if ($flow->isSubmitted() && $flow->isValid() && $flow->isFinished()) {
            $signalement = $signalementServiceSecoursFactory->create($flow->getData(), $serviceSecoursRoute);

            $entityManager->beginTransaction();
            $signalement->setReference($referenceGenerator->generateReference($signalement->getTerritory()));
            $entityManager->persist($signalement);
            $entityManager->flush();
            $entityManager->commit();
            $userManager->createUsagersFromSignalement($signalement);
            $autoAssigner->assignOrSendNewSignalementNotification($signalement);
            $pdfContent = $serviceSecoursPdfGenerator->generate($signalement);
            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_SERVICE_SECOURS_ACCUSE_RECEPTION,
                    to: $serviceSecoursRoute->getEmail(),
                    signalement: $signalement,
                    attachment: $pdfContent,
                )
            );
            $messageBus->dispatch(new SignalementServiceSecoursFileMessage($signalement->getId()));

            return $this->render('service_secours/success.html.twig', ['serviceSecoursRoute' => $serviceSecoursRoute, 'signalement' => $signalement]);
        }
        $step = $flow->getStepForm()->getCursor()->getCurrentStep();
        $desordres = 'step6' === $step ? $desordreCritereRepository->findAllWithPrecisions(AppContext::SERVICE_SECOURS) : null;

        return $this->render('service_secours/index.html.twig', [
            'form' => $flow->getStepForm(),
            'data' => $flow->getData(),
            'desordres' => $desordres,
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
        if ($signalement->getServiceSecours()?->getId() !== $serviceSecoursRoute->getId()) {
            throw $this->createNotFoundException();
        }
        $pdfContent = $serviceSecoursPdfGenerator->generate($signalement);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="signalement-'.$signalement->getReference().'.pdf"');

        return $response;
    }

    #[Route('/services-secours/{slug:serviceSecoursRoute}/{uuid:serviceSecoursRoute}/site.webmanifest',
        name: 'service_secours_webmanifest',
        methods: ['GET'])]
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
        requirements: ['any' => '(?!_wdt/|_profiler/|csp-report|bailleurs|signalement/handle).*'],
    )]
    public function fallback(): Response
    {
        throw $this->createNotFoundException();
    }
}
