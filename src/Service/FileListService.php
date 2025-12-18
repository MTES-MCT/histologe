<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\FileRepository;
use Symfony\Bundle\SecurityBundle\Security;

class FileListService
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly Security $security,
        private readonly FileVisibilityService $fileVisibilityService,
    ) {
    }

    /** @return array<mixed> */
    public function getFileChoicesForSignalement(Signalement $signalement): array
    {
        $choices = [];

        // 1. Documents de la situation (ex "Documents du dossier")
        $situationFiles = $signalement->getFiles()->filter(function (File $file) {
            return $file->isSituation() || null === $file->getDocumentType();
        });
        $situationFilesSorted = $situationFiles->toArray();
        usort($situationFilesSorted, function (File $a, File $b) {
            return strcmp($a->getTitle(), $b->getTitle());
        });
        $choices['Documents de la situation'] = $situationFilesSorted;

        // 2. Documents liés à la procédure
        $procedureFiles = $signalement->getFiles()->filter(function (File $file) {
            return $file->isProcedure();
        });
        if (!$procedureFiles->isEmpty()) {
            $procedureFilesSorted = $procedureFiles->toArray();
            usort($procedureFilesSorted, function (File $a, File $b) {
                return strcmp($a->getTitle(), $b->getTitle());
            });
            $choices['Documents liés à la procédure'] = $procedureFilesSorted;
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
