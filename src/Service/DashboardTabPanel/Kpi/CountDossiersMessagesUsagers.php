<?php

namespace App\Service\DashboardTabPanel\Kpi;

readonly class CountDossiersMessagesUsagers
{
    public function __construct(
        public int $countSuivisUsagersWithoutAskFeedbackBefore = 0,
        public int $countSuivisPostCloture = 0,
        public int $countSuivisUsagerOrPoursuiteWithAskFeedbackBefore = 0,
    ) {
    }

    public function total(): int
    {
        return $this->countSuivisUsagersWithoutAskFeedbackBefore
            + $this->countSuivisPostCloture
            + $this->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore;
    }
}
