<?php

namespace App\Service\Signalement\Export;

use App\Entity\Signalement;
use Knp\Snappy\Pdf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementExportPdfGenerator
{
    public function __construct(private readonly Pdf $pdf, private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function generate(string $content, ?array $options = null): string
    {
        return $this->pdf->getOutputFromHtml($content, $options ?? []);
    }

    public function generateToTempFolder(
        Signalement $signalement,
        string $content,
        ?array $options = null
    ): string {
        $pdfContent = $this->generate($content, $options);

        $filename = 'export-pdf-signalement-'.$signalement->getUuid().'.pdf';
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        file_put_contents($tmpFilepath, $pdfContent);

        return $filename;
    }
}
