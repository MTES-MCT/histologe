<?php

namespace App\Factory;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\SuiviDelayed;
use App\Entity\User;

class SuiviDelayedFactory
{
    public function createSuiviDelayedFromSignalementChanges(
        User $user,
        Signalement $signalement,
    ): SuiviDelayed {
        $suiviDelayed = new SuiviDelayed();

        $type = SuiviDelayedType::from($signalement->getChanges()['suiviDelayedType']);
        $changes = $signalement->getChanges()['fieldChanges'];

        $suiviDelayed->setSuiviCategory(SuiviCategory::SIGNALEMENT_EDITED_FO);
        $suiviDelayed->setSuiviDelayedType($type);
        $suiviDelayed->setChanges($changes);
        $suiviDelayed->setUser($user);
        $suiviDelayed->setSignalement($signalement);

        return $suiviDelayed;
    }

    /**
     * @param iterable<File> $filesToAttach
     * @param array<mixed>   $customChanges
     */
    public function createSuiviDelayed(
        User $user,
        Signalement $signalement,
        SuiviDelayedType $type,
        iterable $filesToAttach = [],
        array $customChanges = [],
    ): SuiviDelayed {
        $suiviDelayed = new SuiviDelayed();

        $suiviDelayed->setSuiviCategory(SuiviCategory::SIGNALEMENT_EDITED_FO);
        $suiviDelayed->setSuiviDelayedType($type);
        $suiviDelayed->setUser($user);
        $suiviDelayed->setSignalement($signalement);
        if (!empty($customChanges)) {
            $suiviDelayed->setChanges($customChanges);
        }

        foreach ($filesToAttach as $file) {
            $file->setSuiviDelayed($suiviDelayed);
        }

        return $suiviDelayed;
    }
}
