<?php

namespace App\Controller;

use App\Dto\DemandeLienSignalement;
use App\Form\DemandeLienSignalementType;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'SITES_FACILES_URL')]
        private readonly string $sitesFacilesUrl,
    ) {
    }

    #[Route(
        '/',
        name: 'home',
        defaults: ['show_sitemap' => true]
    )]
    public function index(
        #[Autowire(param: 'kernel.environment')]
        string $environment,
    ): Response {
        if ('prod' === $environment) {
            return $this->redirect($this->sitesFacilesUrl, Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/demande-lien-signalement', name: 'front_demande_lien_signalement', methods: ['POST'])]
    public function demandeLienSignalement(
        Request $request,
        SignalementRepository $signalementRepository,
        NotificationMailerRegistry $notificationMailerRegistry,
        RateLimiterFactory $askLinkFormLimiter,
    ): JsonResponse {
        $demandeLienSignalement = new DemandeLienSignalement();
        $form = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $askLinkFormLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                $view = $this->renderView('_partials/_demande-lien-signalement-rate-limit.html.twig', ['form' => $form]);

                return new JsonResponse(['html' => $view]);
            }

            $signalement = $signalementRepository->findOneForEmailAndAddress(
                $demandeLienSignalement->getEmail(),
                $demandeLienSignalement->getAdresse(),
                $demandeLienSignalement->getCodePostal(),
                $demandeLienSignalement->getVille()
            );
            if ($signalement) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_SIGNALEMENT_LIEN_SUIVI_TO_USAGER,
                        to: $demandeLienSignalement->getEmail(),
                        signalement: $signalement,
                    )
                );
            }
            $view = $this->renderView('_partials/_demande-lien-signalement-ok.html.twig', ['form' => $form]);

            return new JsonResponse(['html' => $view]);
        }

        $view = $this->renderView('form/form-demande-lien-signalement.html.twig', ['form' => $form]);

        return new JsonResponse(['html' => $view]);
    }

    #[Route(
        '/qui-sommes-nous',
        name: 'front_about',
        defaults: ['show_sitemap' => true]
    )]
    public function about(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'a-propos/qui-sommes-nous/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/entretien-logement-obligations-proprietaire-locataire',
        name: 'front_obligations_entretien',
        defaults: ['show_sitemap' => true]
    )]
    public function obligations_entretien(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'blog/entretien-logement-qui-paye-quoi/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/aides-travaux-logement',
        name: 'front_aides_travaux',
        defaults: ['show_sitemap' => true]
    )]
    public function aides_travaux(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'blog/quelles-aides-pour-faire-des-travaux-dans-mon-logement/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/contact',
        name: 'front_contact',
        methods: ['GET'],
        defaults: ['show_sitemap' => false]
    )]
    public function contact(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'une-question/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/cgu',
        name: 'front_cgu',
        defaults: ['show_sitemap' => true]
    )]
    public function cguUsager(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'cgu', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/cgu-agents',
        name: 'front_cgu_agents',
        defaults: ['show_sitemap' => true]
    )]
    public function cguPro(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'cgu-agents/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/politique-de-confidentialite',
        name: 'politique_de_confidentialite',
        defaults: ['show_sitemap' => true]
    )]
    public function politiqueConfidentialite(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'politique-de-confidentialite/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/mentions-legales',
        name: 'mentions_legales',
        defaults: ['show_sitemap' => true]
    )]
    public function mentionsLegales(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'mentions-legales/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route(
        '/accessibilite',
        name: 'front_accessibilite',
        defaults: ['show_sitemap' => true]
    )]
    public function accessibilite(): Response
    {
        return $this->redirect($this->sitesFacilesUrl.'accessibilite/', Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/plan-du-site', name: 'plan_du_site')]
    public function planDuSite(): Response
    {
        return $this->redirect($this->sitesFacilesUrl, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Cache(public: true, maxage: 3600)]
    #[Route('/sitemap.{_format}', name: 'app_front_sitemap', defaults: ['_format' => 'xml'])]
    public function generateSitemap()
    {
        return $this->redirect($this->sitesFacilesUrl, Response::HTTP_MOVED_PERMANENTLY);
    }
}
