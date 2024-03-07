<?php

namespace App\Controller;

use App\Entity\Model\DemandeLienSignalement;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        PostalCodeHomeChecker $postalCodeHomeChecker
    ): Response {
        $title = 'Un service public pour les locataires et propriétaires';

        $stats = ['pris_en_compte' => 0, 'clotures' => 0];
        $stats['total'] = $signalementRepository->countAll(
            territory: null,
            partner: null,
            removeImported: true,
            removeArchived: true
        );

        if ($stats['total'] > 0) {
            $stats['pris_en_compte'] = round($signalementRepository->countValidated(true) / $stats['total'] * 100, 1);
            $stats['clotures'] = round($signalementRepository->countClosed(true) / $stats['total'] * 100, 1);
        }

        $displayModal = '';

        $form = $this->createForm(PostalCodeSearchType::class, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inputPostalCode = $form->get('postalcode')->getData();
            if ($postalCodeHomeChecker->isActive($inputPostalCode)) {
                return $this->redirectToRoute('front_signalement', ['cp' => $inputPostalCode]);
            }
            $displayModal = $inputPostalCode;
        }

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        return $this->render('front/index.html.twig', [
            'title' => $title,
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
        ): JsonResponse {
        $demandeLienSignalement = new DemandeLienSignalement();
        $form = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $signalement = $signalementRepository->findOneForEmailAndAddress(
                $demandeLienSignalement->getEmail(),
                $demandeLienSignalement->getAdresse(),
                $demandeLienSignalement->getCodePostal(),
                $demandeLienSignalement->getVille()
            );
            if ($signalement) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_SIGNALEMENT_LIEN_SUIVI,
                        to: $demandeLienSignalement->getEmail(),
                        signalement: $signalement,
                    )
                );
            }
            $view = $this->renderView('_partials/_demande-lien-signalement-ok.html.twig', ['form' => $form]);

            return new JsonResponse(['html' => $view]);
        }

        $view = $this->renderView('_partials/_form-demande-lien-signalement.html.twig', ['form' => $form]);

        return new JsonResponse(['html' => $view]);
    }

    #[Route('/qui-sommes-nous', name: 'front_about')]
    public function about(): Response
    {
        $title = 'Qui sommes-nous ?';

        return $this->render('front/about.html.twig', [
            'title' => $title,
        ]);
    }

    #[Route('/contact', name: 'front_contact')]
    public function contact(
        Request $request,
        ContactFormHandler $contactFormHandler,
    ): Response {
        $title = 'Contact';
        $form = $this->createForm(ContactType::class, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactFormHandler->handle(
                $form->get('nom')->getData(),
                $form->get('email')->getData(),
                $form->get('message')->getData()
            );
            $this->addFlash('success', 'Votre message à bien été envoyé !');

            return $this->redirectToRoute('front_contact');
        }

        return $this->render('front/contact.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cgu', name: 'front_cgu')]
    public function cguUsager(): Response
    {
        return $this->render('front/cgu_usagers.html.twig');
    }

    #[Route('/cgu-agents', name: 'front_cgu_agents')]
    public function cguPro(): Response
    {
        return $this->render('front/cgu_agents.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'politique_de_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('front/politique_de_confidentialite.html.twig');
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('front/mentions_legales.html.twig');
    }

    #[Route('/accessibilite', name: 'front_accessibilite')]
    public function accessibilite(): Response
    {
        return $this->render('front/accessibilite.html.twig');
    }
}
