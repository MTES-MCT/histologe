<?php

namespace App\Service\Signalement\Export;

use Knp\Snappy\Pdf;

class SignalementExportPdf
{
    public function __construct(private readonly Pdf $pdf)
    {
    }

    public function generate(string $content, ?array $options = null)
    {
        return $this->pdf->getOutputFromHtml($content, $options ?? []);
    }
}
