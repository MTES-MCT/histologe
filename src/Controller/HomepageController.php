<?php

namespace App\Controller;

use App\Dto\DemandeLienSignalement;
use App\Form\ContactType;
use App\Form\DemandeLienSignalementType;
use App\Form\PostalCodeSearchType;
use App\FormHandler\ContactFormHandler;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\PostalCodeHomeChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomepageController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $cache,
        #[Autowire(env: 'SITES_FACILES_URL')]
        private readonly string $sitesFacilesUrl,
        #[Autowire(env: 'FEATURE_SITES_FACILES')]
        private readonly bool $featureSitesFaciles,
    ) {
    }

    #[Route(
        '/',
        name: 'home',
        defaults: ['show_sitemap' => true]
    )]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        PostalCodeHomeChecker $postalCodeHomeChecker,
        #[Autowire(param: 'kernel.environment')]
        string $environment,
    ): Response {
        if ($this->featureSitesFaciles && 'prod' === $environment) {
            return $this->redirect($this->sitesFacilesUrl, Response::HTTP_MOVED_PERMANENTLY);
        }
        if ($this->featureSitesFaciles) {
            return $this->redirectToRoute('app_login');
        }

        $stats = $this->cache->get('homepage.stats.array', function (ItemInterface $item) use ($signalementRepository) {
            $item->expiresAfter(900);

            $stats = ['pris_en_compte' => 0, 'clotures' => 0];
            $stats['total'] = $signalementRepository->countAll(
                territory: null,
                partners: null,
                removeImported: true,
                removeArchived: true
            );

            if ($stats['total'] > 0) {
                $stats['pris_en_compte'] = round($signalementRepository->countValidated(true) / $stats['total'] * 100, 1);
                $stats['clotures'] = round($signalementRepository->countClosed(true) / $stats['total'] * 100, 1);
            }

            return $stats;
        });

        $displayModal = '';

        $form = $this->createForm(PostalCodeSearchType::class, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inputPostalCode = $form->get('postalcode')->getData();
            if ($postalCodeHomeChecker->isActiveByPostalCode($inputPostalCode)) {
                return $this->redirectToRoute('front_signalement', ['cp' => $inputPostalCode]);
            }
            $displayModal = $inputPostalCode;
        }

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        return $this->render('front/index.html.twig', [
            'form_postalcode' => $form->createView(),
            'stats' => $stats,
            'display_modal' => $displayModal,
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
        ]);
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
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'a-propos/qui-sommes-nous/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/about.html.twig');
    }

    #[Route(
        '/entretien-logement-obligations-proprietaire-locataire',
        name: 'front_obligations_entretien',
        defaults: ['show_sitemap' => true]
    )]
    public function obligations_entretien(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'blog/entretien-logement-qui-paye-quoi/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/obligations_entretien.html.twig', [
            'guide_path' => 'build/files/GUIDE-QUI-REPARE-QUI-ENTRETIENT-Ministere2016-1.pdf',
        ]);
    }

    #[Route(
        '/aides-travaux-logement',
        name: 'front_aides_travaux',
        defaults: ['show_sitemap' => true]
    )]
    public function aides_travaux(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'blog/quelles-aides-pour-faire-des-travaux-dans-mon-logement/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/aides_travaux.html.twig');
    }

    #[Route(
        '/contact',
        name: 'front_contact',
        methods: ['GET'],
        defaults: ['show_sitemap' => false]
    )]
    public function contact(
        ParameterBagInterface $parameterBag,
    ): Response {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'une-question/', Response::HTTP_MOVED_PERMANENTLY);
        }
        $form = $this->createForm(ContactType::class, null, [
            'action' => $this->generateUrl('front_contact_form'),
        ]);
        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        return $this->render('front/contact.html.twig', [
            'form' => $form->createView(),
            'contactEmail' => $parameterBag->get('contact_email'),
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
        ]);
    }

    #[Route('/contact', name: 'front_contact_form', methods: ['POST'])]
    public function contactForm(
        Request $request,
        ContactFormHandler $contactFormHandler,
        RateLimiterFactory $contactFormLimiter,
    ): Response {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'une-question/', Response::HTTP_MOVED_PERMANENTLY);
        }
        $type = 'error';
        $message = null;
        $form = $this->createForm(ContactType::class, null, [
            'action' => $this->generateUrl('front_contact_form'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $contactFormLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                $message = 'Vous avez atteint le nombre maximum de messages que vous pouvez envoyer. Veuillez réessayer plus tard.';
                $view = $this->renderView('form/form-contact.html.twig', ['form' => $form, 'type' => $type, 'message' => $message]);

                return new JsonResponse(['html' => $view]);
            }
            $contactFormHandler->handle(
                $form->get('nom')->getData(),
                $form->get('email')->getData(),
                $form->get('message')->getData(),
                (string) $form->get('organisme')->getData(),
                $form->get('objet')->getData()
            );
            $type = 'success';
            $message = 'Votre message à bien été envoyé !';
        }
        $view = $this->renderView('form/form-contact.html.twig', ['form' => $form, 'type' => $type, 'message' => $message]);

        return new JsonResponse(['html' => $view]);
    }

    #[Route(
        '/cgu',
        name: 'front_cgu',
        defaults: ['show_sitemap' => true]
    )]
    public function cguUsager(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'cgu', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/cgu_usagers.html.twig');
    }

    #[Route(
        '/cgu-agents',
        name: 'front_cgu_agents',
        defaults: ['show_sitemap' => true]
    )]
    public function cguPro(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'cgu-agents/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/cgu_agents.html.twig');
    }

    #[Route(
        '/politique-de-confidentialite',
        name: 'politique_de_confidentialite',
        defaults: ['show_sitemap' => true]
    )]
    public function politiqueConfidentialite(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'politique-de-confidentialite/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/politique_de_confidentialite.html.twig');
    }

    #[Route(
        '/mentions-legales',
        name: 'mentions_legales',
        defaults: ['show_sitemap' => true]
    )]
    public function mentionsLegales(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'mentions-legales/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/mentions_legales.html.twig');
    }

    #[Route(
        '/accessibilite',
        name: 'front_accessibilite',
        defaults: ['show_sitemap' => true]
    )]
    public function accessibilite(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl.'accessibilite/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/accessibilite.html.twig');
    }

    #[Route('/plan-du-site', name: 'plan_du_site')]
    public function planDuSite(): Response
    {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl, Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('front/plan_du_site.html.twig');
    }

    #[Cache(public: true, maxage: 3600)]
    #[Route('/sitemap.{_format}', name: 'app_front_sitemap', defaults: ['_format' => 'xml'])]
    public function generateSitemap(
        RouterInterface $router,
        #[Autowire(param: 'host_url')]
        string $hostUrl,
    ) {
        if ($this->featureSitesFaciles) {
            return $this->redirect($this->sitesFacilesUrl, Response::HTTP_MOVED_PERMANENTLY);
        }
        $urls = [];
        $routes = $router->getRouteCollection()->all();
        foreach ($routes as $route) {
            if ($route->getDefaults()['show_sitemap'] ?? false) {
                $urls[] = ['loc' => $hostUrl.$route->getPath()];
            }
        }

        return new Response(
            $this->renderView('front/sitemap.xml.twig', ['urls' => $urls]),
            Response::HTTP_OK,
            ['Content-Type' => 'text/xml']
        );
    }
}
