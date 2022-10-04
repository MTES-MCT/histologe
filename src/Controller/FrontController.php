<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Form\PostalCodeSearchType;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Service\ConfigurationService;
use App\Service\NotificationService;
use App\Service\Signalement\PostalCodeHomeChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        AffectationRepository $affectationRepository,
        PostalCodeHomeChecker $postalCodeHomeChecker): Response
    {
        $title = 'Un service public pour les locataires et propriétaires';

        $stats = [];
        $stats['total'] = 11979;
        $stats['pris_en_compte'] = '99,8';
        $stats['clotures'] = '68,9';

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

    #[Route('/statistiques', name: 'front_statistiques')]
    public function statistiques(): Response
    {
        $title = 'Statistiques';

        $stats = [];
        $stats['total'] = '11 979';
        $stats['pris_en_compte'] = '99,8';
        $stats['clotures'] = '68,9';
        $stats['nb_territoires'] = '15';
        $stats['moyenne_nb_desordres_par_signalement'] = '4,4';
        $stats['moyenne_jours_resolution'] = '225';
        $stats['moyenne_criticite'] = '28,9';

        return $this->render('front/statistiques.html.twig', [
            'title' => $title,
            'stats' => $stats,
        ]);
    }

    #[Route('/contact', name: 'front_contact')]
    public function contact(Request $request, NotificationService $notificationService, ConfigurationService $configurationService): Response
    {
        $title = "Conditions Générales d'Utilisation";
        $form = $this->createForm(ContactType::class, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notificationService->send(
                NotificationService::TYPE_CONTACT_FORM,
                $this->getParameter('notifications_email'),
                [
                    'nom' => $form->get('nom')->getData(),
                    'mail' => $form->get('email')->getData(),
                    'reply' => $form->get('email')->getData(),
                    'message' => nl2br($form->get('message')->getData()),
                ],
                null
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
