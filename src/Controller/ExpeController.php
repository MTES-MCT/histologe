<?php

namespace App\Controller;

use App\Entity\Territory;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expe')]
class ExpeController extends AbstractController
{
    #[Route('/rnb-selecteur-batiment', name: 'expe_rnb_selecteur_batiment')]
    public function rnbSelecteurBatiment()
    {
        return $this->render('expe/selecteur-batiment.html.twig');
    }

    #[Route('/zone-signalement-territory/{zip}', name: 'expe_zone_signalement_territory')]
    public function zoneDb(Territory $territory, ZoneRepository $zoneRepository)
    {
        $zone = $zoneRepository->findOneBy(['territory' => $territory]);
        $locations = $zoneRepository->findLocationsByZone($zone);

        return $this->render('expe/zone-signalement-territory.html.twig', [
            'zone' => $zone, 'locations' => $locations,
        ]);
    }
}
