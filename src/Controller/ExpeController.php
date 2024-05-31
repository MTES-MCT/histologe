<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expe')]
class ExpeController extends AbstractController
{

    #[Route('/rnb-vector-tile', name: 'expe_rnb_vector_tile')]
    public function rnbVectorTile()
    {
        return $this->render('expe/rnb/vector-tile.html.twig');
    }

    
}