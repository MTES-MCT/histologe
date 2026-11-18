<?php

namespace App\Service;

use App\Entity\ClubEvent;
use App\Entity\User;
use App\Repository\ClubEventRepository;

class ClubEventService
{
    public function __construct(
        private readonly ClubEventRepository $clubEventRepository,
    ) {
    }

    public function getNextClubEventForUser(User $user): ?ClubEvent
    {
        return $this->clubEventRepository->findNextForUser($user);
    }
}
