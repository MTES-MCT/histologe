<?php

namespace App\Service;

use App\Controller\FileController;
use App\Entity\Enum\DocumentType;
use App\Entity\SuiviFile;
use App\Repository\DesordreCritereRepository;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviTransformerService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UrlSignerInterface $urlSigner,
        private readonly DesordreCritereRepository $desordreCritereRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param Collection<int, SuiviFile> $suiviFiles
     */
    public function transformDescription(string $description, Collection $suiviFiles): string
    {
        $description = $this->replaceStaticLinkToFiles($description);
        $description .= $this->addLinkToFiles($suiviFiles);

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

    /**
     * @param Collection<int, SuiviFile> $suiviFiles
     */
    private function addLinkToFiles(Collection $suiviFiles): string
    {
        if ($suiviFiles->isEmpty()) {
            return '';
        }
        $description = '<div class="fr-mt-2v">';
        $description .= '<i>';
        $description .= count($suiviFiles) > 1 ? count($suiviFiles).' Fichiers joints :' : '1 Fichier joint :';
        $description .= '</i>';
        $description .= '<ul>';
        foreach ($suiviFiles as $suiviFile) {
            $description .= '<li>';
            if ($suiviFile->getFile()) {
                $url = $this->urlGenerator->generate('show_file', ['uuid' => $suiviFile->getFile()->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
                $url = $this->urlSigner->sign($url, FileController::SIGNATURE_VALIDITY_DURATION);
                $description .= '<a class="fr-link" target="_blank" rel="noopener" href="'.$url.'">'.$suiviFile->getTitle().'</a>';
                if ($suiviFile->getFile()->getDocumentType() && DocumentType::AUTRE !== $suiviFile->getFile()->getDocumentType()) {
                    if (DocumentType::PHOTO_SITUATION === $suiviFile->getFile()->getDocumentType() && null !== $suiviFile->getFile()->getDesordreSlug()) {
                        $desordreCritere = $this->desordreCritereRepository->findOneBy(['slugCritere' => $suiviFile->getFile()->getDesordreSlug()]);
                        if (!$desordreCritere) {
                            $desordreCritere = $this->desordreCritereRepository->findOneBy(['slugCategorie' => $suiviFile->getFile()->getDesordreSlug()]);
                        }
                        if ($desordreCritere) {
                            $description .= ' <small>('.$suiviFile->getFile()->getDocumentType()->label().' - '.$desordreCritere->getLabelCritere().')</small>';
                        } else {
                            $this->logger->error(\sprintf('$desordreCritere not found with slugCritere or slugCategorie = %s', $suiviFile->getFile()->getDesordreSlug()));
                            $description .= ' <small>('.$suiviFile->getFile()->getDocumentType()->label().' - désordre non défini)</small>';
                        }
                    } else {
                        $description .= ' <small>('.$suiviFile->getFile()->getDocumentType()->label().')</small>';
                    }
                }
            } else {
                $description .= 'Fichier supprimé ('.$suiviFile->getTitle().')';
            }
            $description .= '</li>';
        }
        $description .= '</ul>';
        $description .= '</div>';

        return $description;
    }
}
