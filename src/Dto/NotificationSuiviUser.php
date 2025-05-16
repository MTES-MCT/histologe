<?php

namespace App\Dto;

readonly class NotificationSuiviUser
{
    public function __construct(
        public int $suiviId,
        public int $userId,
        public bool $isSeen,
        public \DateTimeImmutable $seenAt,
    ) {
    }
}
