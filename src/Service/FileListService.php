<?php

namespace App\Service;

use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\SignalementQualificationRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FileListService
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly SignalementQualificationRepository $signalementQualificationRepository,
        private readonly Security $security,
        private readonly QualificationStatusService $qualificationStatusService,
        private readonly FileVisibilityService $fileVisibilityService,
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')]
        private readonly bool $featureNewDocumentSpace,
    ) {
    }

    /** @return array<mixed> */
    public function getFileChoicesForSignalement(Signalement $signalement): array
    {
        if (!$this->featureNewDocumentSpace) {
            // Ancien comportement
            $signalementFiles = $signalement->getFiles()->filter(function (File $file) {
                return $file->isTypeDocument() && !$file->getIsSuspicious();
            });
            $signalementQualificationNDE = $this->signalementQualificationRepository->findOneBy([
                'signalement' => $signalement,
                'qualification' => Qualification::NON_DECENCE_ENERGETIQUE,
            ]);
            $choices = ['Documents du dossier' => $signalementFiles->toArray()];
            if ($this->security->isGranted('SIGN_SEE_NDE', $signalement) && $this->qualificationStatusService->canSeenNDEEditZone($signalementQualificationNDE)) {
                $standaloneFiles = $this->fileRepository->findBy(['isStandalone' => true], ['title' => 'ASC']);
                $choices['Documents types'] = $standaloneFiles;
            }

            return $choices;
        }

        // Nouveau comportement avec FEATURE_NEW_DOCUMENT_SPACE
        $choices = [];

        // 1. Documents de la situation (ex "Documents du dossier")
        $situationFiles = $signalement->getFiles()->filter(function (File $file) {
            return !$file->getIsSuspicious() && ($file->isSituation() || null === $file->getDocumentType());
        });
        $choices['Documents de la situation'] = $situationFiles->toArray();

        // 2. Documents liés à la procédure
        $procedureFiles = $signalement->getFiles()->filter(function (File $file) {
            return !$file->getIsSuspicious() && $file->isProcedure();
        });
        if (!$procedureFiles->isEmpty()) {
            $choices['Documents liés à la procédure'] = $procedureFiles->toArray();
        }

        // 3. Documents types (fichiers standalone)
        $standaloneFiles = $this->fileRepository->createQueryBuilder('f')
            ->where('f.isStandalone = :isStandalone')
            ->andWhere('f.territory = :territory OR f.territory IS NULL')
            ->setParameter('isStandalone', true)
            ->setParameter('territory', $signalement->getTerritory())
            ->orderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult();

        /** @var User $user */
        $user = $this->security->getUser();
        $standaloneFiles = $this->fileVisibilityService->filterFilesForUser($standaloneFiles, $user);

        if (!empty($standaloneFiles)) {
            // Grouper les fichiers par documentType
            $standaloneFilesByType = [];
            foreach ($standaloneFiles as $file) {
                $documentType = $file->getDocumentType();
                $typeLabel = $documentType ? $documentType->label() : 'Autres';

                if (!isset($standaloneFilesByType[$typeLabel])) {
                    $standaloneFilesByType[$typeLabel] = [];
                }
                $standaloneFilesByType[$typeLabel][] = $file;
            }

            // Créer une structure avec des sous-catégories pour Documents types
            ksort($standaloneFilesByType);
            $choices['Documents types'] = $standaloneFilesByType;
        }

        return $choices;
    }
}
