<?php

namespace App\Service\DashboardTabPanel\Kpi;

readonly class CountDossiersMessagesUsagers
{
    public function __construct(
        public int $countLastMessageUsagerWithoutAskFeedbackBefore = 0,
        public int $countLastMessageUsagerIsPostCloture = 0,
        public int $countLastMessageUsagerWithAskFeedbackBefore = 0,
    ) {
    }

    public function total(): int
    {
        return $this->countLastMessageUsagerWithoutAskFeedbackBefore
            + $this->countLastMessageUsagerIsPostCloture
            + $this->countLastMessageUsagerWithAskFeedbackBefore;
    }
}
