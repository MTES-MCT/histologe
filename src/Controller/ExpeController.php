<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expe')]
class ExpeController extends AbstractController
{

    #[Route('/rnb-selecteur-batiment', name: 'expe_rnb_selecteur_batiment')]
    public function rnbVectorTile()
    {
        return $this->render('expe/rnb/selecteur-batiment.html.twig');
    }

    
}