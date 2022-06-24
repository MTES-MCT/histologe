<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\ConfigurationService;
use App\Service\NotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function index(SignalementRepository $signalementRepository, EntityManagerInterface $entityManager, TerritoryRepository $territoryRepository, AffectationRepository $affectationRepository, EntityManagerInterface $doctrine, NotificationService $notificationService): Response
    {
        $title = 'Un service public pour les locataires et propriétaires';
        $year = (new DateTimeImmutable())->format('Y');
        $total = $signalementRepository->findAllWithAffectations($year);
        $stats['total'] = count($total);
        $stats['pec'] = $stats['total'] !== 0 ? floor(($affectationRepository->createQueryBuilder('a')->select('COUNT(DISTINCT a.signalement)')->join('a.signalement', 'signalement', 'WITH', 'signalement.statut != 7 AND YEAR(signalement.createdAt) = ' . $year)->getQuery()->getSingleScalarResult() / $stats['total']) * 100) : 0;
        $stats['res'] = $stats['total'] !== 0 ? floor(($affectationRepository->createQueryBuilder('a')->select('COUNT(DISTINCT a.signalement)')->where('a.statut = 1')->join('a.signalement', 'signalement', 'WITH', 'signalement.statut != 7 AND YEAR(signalement.createdAt) = ' . $year)->getQuery()->getSingleScalarResult() / $stats['total']) * 100) : 0;
        return $this->render('front/index.html.twig', [
            'title' => $title,
            'stats' => $stats
        ]);
    }

    #[Route('/qui-sommes-nous', name: 'front_about')]
    public function about(): Response
    {
        $title = 'Qui sommes-nous ?';
        return $this->render('front/about.html.twig', [
            'title' => $title
        ]);
    }

    #[Route('/contact', name: 'front_contact')]
    public function contact(Request $request, NotificationService $notificationService, ConfigurationService $configurationService): Response
    {
        $title = "Conditions Générales d'Utilisation";
        $form = $this->createForm(ContactType::class, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notificationService->send(NotificationService::TYPE_CONTACT_FORM, ['notifications@histologe.fr', $configurationService->get()->getEmailReponse()], [
                'nom' => $form->get('nom')->getData(),
                'mail' => $form->get('email')->getData(),
                'reply' => $form->get('email')->getData(),
                'message' => nl2br($form->get('message')->getData()),
            ]);
            $this->addFlash('success', 'Votre message à bien été envoyé !');
            return $this->redirectToRoute('front_contact');
        }
        return $this->render('front/contact.html.twig', [
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route('/cgu', name: 'front_cgu')]
    public function cgu(): Response
    {
        $title = "Conditions Générales d'Utilisation";
        return $this->render('front/cgu.html.twig', [
            'title' => $title
        ]);
    }
}
