<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Repository\SignalementRepository;
use App\Service\Signalement\Export\SignalementExportPdfGenerator;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class SignalementExportPdfTest extends KernelTestCase
{
    public function testGeneratePdf()
    {
        self::bootKernel();
        $pdf = static::getContainer()->get(Pdf::class);
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $signalementExportPdfGenerator = new SignalementExportPdfGenerator($pdf, $parameterBag);

        $twig = static::getContainer()->get(Environment::class);
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);

        $html = $twig->render('pdf/signalement.html.twig', [
            'signalement' => $signalement,
            'situations' => [],
            'listQualificationStatusesLabelsCheck' => [],
        ]);
        $options = static::getContainer()->getParameter('export_options');
        $pdfContent = $signalementExportPdfGenerator->generate($html, $options);
        $this->assertNotEmpty($pdfContent);
        $this->assertStringStartsWith('%PDF-', $pdfContent);
    }
}
