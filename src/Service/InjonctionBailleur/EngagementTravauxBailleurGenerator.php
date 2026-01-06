<?php

namespace App\Service\InjonctionBailleur;

use App\Entity\Signalement;
use Dompdf\Dompdf;
use Twig\Environment;

class EngagementTravauxBailleurGenerator
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function generate(Signalement $signalement): string
    {
        $content = $this->twig->render('back/signalement-injonction/engagement-travaux-bailleur.html.twig', ['signalement' => $signalement]);

        $domPdf = new Dompdf();
        $domPdf->loadHtml($content);
        $domPdf->render();

        return $domPdf->output();
    }
}
