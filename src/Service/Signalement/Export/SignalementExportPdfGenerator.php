<?php

namespace App\Service\Signalement\Export;

use App\Entity\Signalement;
use Dompdf\Dompdf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementExportPdfGenerator
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function generate(string $content): string
    {
        $tmp = $this->parameterBag->get('uploads_tmp_dir');
        if (str_ends_with($tmp, '/')) {
            $tmp = substr($tmp, 0, -1);
        }

        $domPdf = new Dompdf([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'fontDir' => $tmp,
            'fontCache' => $tmp,
            'tempDir' => $tmp,
            'chroot' => $tmp,
        ]);

        $domPdf->loadHtml($content);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();

        return $domPdf->output();
    }

    public function generateToTempFolder(
        Signalement $signalement,
        string $content,
        bool $isForUsager = false,
    ): string {
        $pdfContent = $this->generate($content);

        if ($isForUsager) {
            $filename = 'export-pdf-dossier-'.$signalement->getUuid().'.pdf';
        } else {
            $filename = 'export-pdf-signalement-'.$signalement->getUuid().'.pdf';
        }
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        file_put_contents($tmpFilepath, $pdfContent);

        return $filename;
    }
}
