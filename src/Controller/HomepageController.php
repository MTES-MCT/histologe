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
        $title = 'Un service public pour les locataires et propriétaires';

        $stats = [];
        $stats['total'] = $signalementRepository->countAll(
            territory: null,
            removeImported: true,
            removeArchived: true
        );
        $stats['pris_en_compte'] = round($signalementRepository->countValidated(true) / $stats['total'] * 100, 1);
        $stats['clotures'] = round($signalementRepository->countClosed(true) / $stats['total'] * 100, 1);

        $display_modal = '';

        $form = $this->createForm(PostalCodeSearchType::class, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inputPostalCode = $form->get('postalcode')->getData();
            if ($postalCodeHomeChecker->isActive($inputPostalCode)) {
                return $this->redirectToRoute('front_signalement');
            }
            $display_modal = $inputPostalCode;
        }

        return $this->render('front/index.html.twig', [
            'title' => $title,
            'form_postalcode' => $form->createView(),
            'stats' => $stats,
            'display_modal' => $display_modal,
        ]);
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
        $title = "Conditions Générales d'Utilisation";
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
    public function cgu(): Response
    {
        $title = "Conditions Générales d'Utilisation";

        return $this->render('front/cgu.html.twig', [
            'title' => $title,
        ]);
    }
}
