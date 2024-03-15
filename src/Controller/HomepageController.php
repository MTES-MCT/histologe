<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Form\PostalCodeSearchType;
use App\FormHandler\ContactFormHandler;
use App\Repository\SignalementRepository;
use App\Service\Signalement\PostalCodeHomeChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        return $this->render('front/index.html.twig', [
            'form_postalcode' => $form->createView(),
            'stats' => $stats,
            'display_modal' => $displayModal,
        ]);
    }

    #[Route('/qui-sommes-nous', name: 'front_about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }

    #[Route('/aides-travaux-logement', name: 'front_aides_travaux')]
    public function aides_travaux(): Response
    {
        return $this->render('front/aides_travaux.html.twig');
    }

    #[Route('/contact', name: 'front_contact')]
    public function contact(
        Request $request,
        ContactFormHandler $contactFormHandler,
    ): Response {
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
