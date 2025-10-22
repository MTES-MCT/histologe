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
                return true;
            }
            if ($f->getTerritory()) {
                $partners = [$user->getPartnerInTerritory($f->getTerritory())];
            } else {
                $partners = $user->getPartners();
            }

            foreach ($partners as $partner) {
                if (!$partner) {
                    continue;
                }

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
     * @param array<mixed> $PartnerParameter
     * @param array<mixed> $fileParameter
     */
    private function enumsIntersect(array $PartnerParameter, array $fileParameter): bool
    {
        if (empty($fileParameter)) {
            return true;
        }

        $valuesA = array_map(fn ($e) => $e->value, $PartnerParameter);
        $valuesB = array_map(fn ($e) => $e->value, $fileParameter);

        return (bool) array_intersect($valuesA, $valuesB);
    }
}
