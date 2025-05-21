<?php

namespace App\Service\Signalement\Export;

use App\Entity\Signalement;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementExportPdfGenerator
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function generate(string $content): string
    {
        $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'fr', true, 'UTF-8', [15, 15, 15, 15]);
        $html2pdf->writeHTML($content);

        return $html2pdf->output('unused.pdf', 'S');
    }

    public function generateToTempFolder(
        Signalement $signalement,
        string $content,
    ): string {
        $pdfContent = $this->generate($content);

        $filename = 'export-pdf-signalement-'.$signalement->getUuid().'.pdf';
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        file_put_contents($tmpFilepath, $pdfContent);

        return $filename;
    }
}
