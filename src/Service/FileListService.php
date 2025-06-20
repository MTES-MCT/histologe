<?php

namespace App\Service;

use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Signalement;
use App\Repository\FileRepository;
use App\Repository\SignalementQualificationRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Symfony\Bundle\SecurityBundle\Security;

class FileListService
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly SignalementQualificationRepository $signalementQualificationRepository,
        private readonly Security $security,
        private readonly QualificationStatusService $qualificationStatusService,
    ) {
    }

    public function getFileChoicesForSignalement(Signalement $signalement): array
    {
        $signalementFiles = $signalement->getFiles()->filter(function (File $file) {
            return $file->isTypeDocument() && !$file->getIsSuspicious();
        });
        $signalementQualificationNDE = $this->signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE,
        ]);
        if ($this->security->isGranted('SIGN_SEE_NDE', $signalement) && $this->qualificationStatusService->canSeenNDEEditZone($signalementQualificationNDE)) {
            $standaloneFiles = $this->fileRepository->findBy(['isStandalone' => true], ['title' => 'ASC']);
            $choices = [
                'Documents du dossier' => $signalementFiles->toArray(),
                'Documents types' => $standaloneFiles,
            ];
        } else {
            $choices = $signalementFiles->toArray();
        }

        return $choices;
    }
}
