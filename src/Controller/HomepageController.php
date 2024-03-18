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
        /*
{"01": 72, "02": 83, "03": 67, "04": 36, "05": 47, "06": 96, "07": 77, "08": 75, "09": 57, "10": 58, "11": 38,
              "12": 33, "13": 89, "14": 24, "15": 52, "16": 41, "17": 79, "18": 38, "19": 42, "21": 25, "22": 26,
              "23": 37, "24": 65, "25": 88, "26": 48, "27": 61, "28": 80, "29": 12, "30": 6, "31": 5, "32": 22, "33": 40,
              "34": 19, "35": 13, "36": 32, "37": 0, "38": 82, "39": 13, "40": 78, "41": 92, "42": 10, "43": 22, "44": 70,
              "45": 85, "46": 58, "47": 72, "48": 61, "49": 27, "50": 47, "51": 41, "52": 44, "53": 29, "54": 22, "55": 4,
              "56": 57, "57": 94, "58": 46, "59": 33, "60": 0, "61": 15, "62": 60, "63": 71, "64": 0, "65": 91, "66": 51,
              "67": 56, "68": 19, "69": 44, "70": 92, "71": 96, "72": 51, "73": 32, "74": 19, "75": 96, "76": 91, "77": 21,
              "78": 48, "79": 72, "80": 52, "81": 48, "82": 57, "83": 38, "84": 23, "85": 46, "86": 37, "87": 64, "88": 78,
              "89": 100, "90": 85, "91": 87, "92": 46, "93": 89, "94": 18, "95": 72, "971": 48, "972": 28, "973": 35,
              "974": 70, "976": 38, "2A": 63, "2B": 16}
        */
        return $this->render('front/about.html.twig', [
            'datatest' => [ '01' => 33, '44' => 65 ]
        ]);
    }

    #[Route('/entretien-logement-obligations-proprietaire-locataire', name: 'front_obligations_entretien')]
    public function obligations_entretien(): Response
    {
        return $this->render('front/obligations_entretien.html.twig', [
            'guide_path' => 'build/files/GUIDE-QUI-REPARE-QUI-ENTRETIENT-Ministere2016-1.pdf',
        ]);
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
