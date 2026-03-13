<?php

namespace App\Service\InjonctionBailleur;

use App\Entity\Signalement;
use Dompdf\Dompdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CourrierBailleurGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
    ) {
    }

    public function generate(Signalement $signalement): string
    {
        $writer = new PngWriter();

        $url = $this->urlGenerator->generate('app_login_bailleur', [], referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode(data: $url);

        $result = $writer->write($qrCode);
        $content = $this->twig->render('back/signalement-injonction/courrier-bailleur.html.twig', [
            'signalement' => $signalement,
            'qrCode' => $result->getDataUri(),
        ]);

        $domPdf = new Dompdf();
        $domPdf->loadHtml($content);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();

        return $domPdf->output();
    }

    public function generateInjonctionClosed(Signalement $signalement): string
    {
        $content = $this->twig->render('back/signalement-injonction/courrier-bailleur-injonction-closed.html.twig', [
            'signalement' => $signalement,
        ]);

        $domPdf = new Dompdf();
        $domPdf->loadHtml($content);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();

        return $domPdf->output();
    }
}
