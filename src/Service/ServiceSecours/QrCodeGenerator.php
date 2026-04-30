<?php

namespace App\Service\ServiceSecours;

use App\Entity\ServiceSecoursRoute;
use App\Utils\UrlHelper;
use Dompdf\Dompdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class QrCodeGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function generate(ServiceSecoursRoute $serviceSecoursRoute): string
    {
        $writer = new PngWriter();
        $url = $this->urlGenerator->generate('service_secours_index', [
            'slug' => $serviceSecoursRoute->getSlug(),
            'uuid' => $serviceSecoursRoute->getUuid(),
            'domain' => UrlHelper::extractRootDomain($this->requestStack->getCurrentRequest()->getHost()),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(data: $url);

        $result = $writer->write($qrCode);
        $content = $this->twig->render('back/config-service-secours-route/qr-code.html.twig', [
            'serviceSecoursRoute' => $serviceSecoursRoute,
            'qrCode' => $result->getDataUri(),
            'url' => $url,
        ]);

        $domPdf = new Dompdf();
        $domPdf->loadHtml($content);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();

        return $domPdf->output();
    }
}
