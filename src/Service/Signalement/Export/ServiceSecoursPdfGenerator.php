<?php

namespace App\Service\Signalement\Export;

use App\Entity\Signalement;
use Dompdf\Dompdf;
use Twig\Environment;

class ServiceSecoursPdfGenerator
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function generate(Signalement $signalement): string
    {
        $content = $this->twig->render('service_secours/pdf.html.twig', ['signalement' => $signalement]);

        $domPdf = new Dompdf();
        $domPdf->loadHtml($content);
        $domPdf->render();

        return $domPdf->output();
    }
}
