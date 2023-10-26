<?php

namespace App\Service\Signalement\Export;

use Knp\Snappy\Pdf;

class SignalementExportPdf
{
    public function __construct(private readonly Pdf $pdf)
    {
    }

    public function generatePdf(string $content, ?array $options)
    {
        return $this->pdf->getOutputFromHtml($content, $options ?? []);
    }
}
