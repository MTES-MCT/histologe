<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Repository\SignalementRepository;
use App\Service\Signalement\Export\SignalementExportPdf;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class SignalementExportPdfTest extends KernelTestCase
{
    public function testGeneratePdf()
    {
        self::bootKernel();
        $pdf = static::getContainer()->get(Pdf::class);
        $signalementExportPdf = new SignalementExportPdf($pdf);

        $twig = static::getContainer()->get(Environment::class);
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);

        $html = $twig->render('pdf/signalement.html.twig', [
            'signalement' => $signalement,
            'situations' => [],
        ]);
        $options = [
            'images' => true,
            'enable-local-file-access' => true,
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
        ];
        $pdfContent = $signalementExportPdf->generatePdf($html, $options);
        $this->assertNotEmpty($pdfContent);
        $this->assertStringStartsWith('%PDF-', $pdfContent);
    }
}
