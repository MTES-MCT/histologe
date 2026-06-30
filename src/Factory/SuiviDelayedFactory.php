<?php

namespace App\Factory;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\SuiviDelayed;
use App\Entity\User;
use App\Manager\UserSignalementSubscriptionManager;

class SuiviDelayedFactory
{
    public function __construct(
        private readonly UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
    ) {
    }

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
        SuiviCategory $category = SuiviCategory::SIGNALEMENT_EDITED_FO,
        array $customChanges = [],
        iterable $filesToAttach = [],
        bool &$subscriptionCreated = false,
    ): SuiviDelayed {
        $suiviDelayed = new SuiviDelayed();

        $suiviDelayed->setSuiviCategory($category);
        $suiviDelayed->setSuiviDelayedType($type);
        $suiviDelayed->setUser($user);
        $suiviDelayed->setSignalement($signalement);
        if (!empty($customChanges)) {
            $suiviDelayed->setChanges($customChanges);
        }

        foreach ($filesToAttach as $file) {
            $file->setSuiviDelayed($suiviDelayed);
        }

        if (SuiviCategory::SIGNALEMENT_EDITED_BO === $category && $this->userSignalementSubscriptionManager->doesUserNeedSubscription($user, $category, $signalement)) {
            $this->userSignalementSubscriptionManager->createOrGet(
                userToSubscribe: $user,
                signalement: $signalement,
                createdBy: $user,
                subscriptionCreated: $subscriptionCreated
            );
        }

        return $suiviDelayed;
    }
}
