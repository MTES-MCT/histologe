<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expe')]
class ExpeController extends AbstractController
{

    #[Route('/rnb-selecteur-batiment', name: 'expe_rnb_selecteur_batiment')]
    public function rnbSelecteurBatminet()
    {
        return $this->render('expe/selecteur-batiment.html.twig');
    }

    #[Route('/zone-signalement', name: 'expe_zone_signalement')]
    public function zone()
    {
        return $this->render('expe/zone-signalement.html.twig');
    }

    
}