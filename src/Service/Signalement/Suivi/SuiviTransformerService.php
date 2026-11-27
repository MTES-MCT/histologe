<?php

namespace App\Service\Signalement\Suivi;

use App\Controller\FileController;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class SuiviTransformerService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UrlSignerInterface $urlSigner,
        private readonly SuiviDescriptionHelper $suiviDescriptionHelper,
        private readonly Environment $twig,
    ) {
    }

    public function transformDescription(Suivi $suivi, bool $transformHtml, SuiviRecipient $recipient): string
    {
        $calculatedDescription = $this->suiviDescriptionHelper->getDisplayDescription($suivi->getCategory(), $recipient);
        if ($calculatedDescription) {
            return $calculatedDescription;
        }
        $description = $suivi->getDescription(raw: true);
        if ($transformHtml && $description) {
            $description = str_replace('&lt;br /&gt;', '<br />', $description);
        }
        $description = $this->replaceStaticLinkToFiles($description);
        $description .= $this->twig->render('suivi/_suivi_files.html.twig', ['suiviFiles' => $suivi->getSuiviFiles()]);
        $description .= $this->addDynamicDataFromSuivi($suivi);

        return $description;
    }

    private function replaceStaticLinkToFiles(string $description): string
    {
        if (!$description) {
            return '';
        }
        preg_match_all('/href="([^"]+)"/', $description, $matches);
        foreach ($matches[1] as $match) {
            if (str_contains($match, '/show/')) {
                $exploded = explode('/show/', $match);
                $uuid = $exploded[count($exploded) - 1];
                $url = $this->urlGenerator->generate('show_file', ['uuid' => $uuid], UrlGeneratorInterface::ABSOLUTE_URL);
                $url = $this->urlSigner->sign($url, FileController::SIGNATURE_VALIDITY_DURATION);
                $description = str_replace($match, $url, $description);
            }
        }

        return $description;
    }

    private function addDynamicDataFromSuivi(Suivi $suivi): string
    {
        if (SuiviCategory::ADD_OR_EDIT_PROCEDURE_ENGAGEE === $suivi->getCategory()) {
            return '<br><p>Rendez-vous dans l\'onglet <a href="#orientation" aria-controls="tabpanel-orientation-panel">Orientation</a> pour voir le détail.</p>';
        }

        return '';
    }
}
