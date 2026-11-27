<?php

namespace App\Service\Files;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Repository\DesordreCritereRepository;
use Psr\Log\LoggerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class FileDocumentTypeLabelResolver implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly DesordreCritereRepository $desordreCritereRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getLabel(File $file): ?string
    {
        if (DocumentType::AUTRE === $file->getDocumentType()) {
            return null;
        }

        if (DocumentType::PHOTO_SITUATION === $file->getDocumentType() && null !== $file->getDesordreSlug()) {
            $desordreCritere = $this->desordreCritereRepository->findOneBy(['slugCritere' => $file->getDesordreSlug()])
                ?? $this->desordreCritereRepository->findOneBy(['slugCategorie' => $file->getDesordreSlug()]);
            if ($desordreCritere) {
                return $file->getDocumentType()->label().' - '.$desordreCritere->getLabelCritere();
            }

            $this->logger->error(\sprintf('$desordreCritere not found with slugCritere or slugCategorie = %s', $file->getDesordreSlug()));

            return $file->getDocumentType()->label().' - désordre non défini';
        }

        return $file->getDocumentType()->label();
    }
}
