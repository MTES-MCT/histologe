<?php

namespace App\Controller;

use App\Repository\DesordreCritereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DocApiController extends AbstractController
{
    #[Route('/doc-api/desordres', name: 'debug', methods: ['GET'])]
    public function debug(DesordreCritereRepository $desordreCritereRepository): Response
    {
        $desordreCriteres = $desordreCritereRepository->findAllWithPrecisions();

        $classifiedDesordres = [];
        foreach ($desordreCriteres as $critere) {
            $classifiedDesordres[$critere->getZoneCategorie()->label()][$critere->getDesordreCategorie()->getLabel()][$critere->getLabelCategorie()][] = $critere;
        }

        return $this->render('doc-api/desordres.html.twig', [
            'classifiedDesordres' => $classifiedDesordres,
        ]);
    }

    // route uniquement utile pour récupérer la liste des slug des désordres et précisions autorisées dans l'API
    // Ces liste sont dupliqué dans les contraintes choices de l'objet DesordreRequest
    #[Route('/bo/identifiants-desordres', name: 'bo_identifiants_desordres', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function identifiantsDesordres(DesordreCritereRepository $desordreCritereRepository): Response
    {
        $desordreCriteres = $desordreCritereRepository->findAllWithPrecisions();

        return $this->render('doc-api/desordres-and-precisions-choices.html.twig', [
            'desordreCriteres' => $desordreCriteres,
        ]);
    }
}
