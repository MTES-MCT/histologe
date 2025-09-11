<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\User;

class FileVisibilityService
{
    /**
     * @param File[] $files
     *
     * @return File[]
     */
    public function filterFilesForUser(array $files, ?User $user): array
    {
        if (null === $user || $user->isSuperAdmin() || $user->isTerritoryAdmin()) {
            return $files;
        }

        return array_filter($files, function (File $f) use ($user) {
            $partnerTypes = $f->getPartnerType() ?? [];
            $partnerCompetences = $f->getPartnerCompetence() ?? [];

            if (empty($partnerTypes) && empty($partnerCompetences)) {
                return true; // pas de restriction
            }

            foreach ($user->getPartners() as $partner) {
                $competenceMatches = $this->enumsIntersect($partner->getCompetence(), $f->getPartnerCompetence());
                $typeMatches = $this->enumsIntersect([$partner->getType()], $f->getPartnerType() ?? []);
                if ($typeMatches && $competenceMatches) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     */
    private function enumsIntersect(array $a, array $b): bool
    {
        if (empty($a) || empty($b)) {
            return true; // pas de restriction
        }

        $valuesA = array_map(fn ($e) => $e->value, $a);
        $valuesB = array_map(fn ($e) => $e->value, $b);

        return (bool) array_intersect($valuesA, $valuesB);
    }
}
