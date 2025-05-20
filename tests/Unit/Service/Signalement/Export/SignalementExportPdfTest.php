<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Repository\SignalementRepository;
use App\Service\Signalement\Export\SignalementExportPdfGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class SignalementExportPdfTest extends KernelTestCase
{
    public function testGeneratePdf()
    {
        self::bootKernel();
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $signalementExportPdfGenerator = new SignalementExportPdfGenerator($parameterBag);

        $twig = static::getContainer()->get(Environment::class);
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);

        $html = $twig->render('pdf/signalement.html.twig', [
            'listConcludeProcedures' => [],
            'signalement' => $signalement,
            'situations' => [],
            'listQualificationStatusesLabelsCheck' => [],
        ]);
        $pdfContent = $signalementExportPdfGenerator->generate($html);
        $this->assertNotEmpty($pdfContent);
        $this->assertStringStartsWith('%PDF-', $pdfContent);
    }
}
