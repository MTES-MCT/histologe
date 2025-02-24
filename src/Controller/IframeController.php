<?php

namespace App\Controller;

use App\Dto\DemandeLienSignalement;
use App\Form\DemandeLienSignalementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/iframe')]
class IframeController extends AbstractController
{
    #[Route('/demande-lien-signalement', name: 'iframe_demande_lien_signalement', methods: ['GET'])]
    public function demandeLienSignalement(): Response
    {
        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        return $this->render('iframe/demande_lien_signalement.html.twig', [
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
        ]);
    }

    #[Route('/stats', name: 'iframe_stats', methods: ['GET'])]
    public function stats(): Response
    {
        return $this->render('iframe/statistiques.html.twig');
    }

    #[Route('/test', name: 'iframe_test', methods: ['GET'])]
    #[When('dev')]
    #[When('test')]
    public function test(): Response
    {
        return $this->render('iframe/test.html.twig');
    }
}
