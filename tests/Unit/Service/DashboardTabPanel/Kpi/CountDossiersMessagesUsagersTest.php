<?php

namespace App\Tests\Service\DashboardTabPanel\Kpi;

use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use PHPUnit\Framework\TestCase;

class CountDossiersMessagesUsagersTest extends TestCase
{
    public function testDefaultValuesAreZero(): void
    {
        $count = new CountDossiersMessagesUsagers();

        $this->assertSame(0, $count->countSuivisUsagersWithoutAskFeedbackBefore);
        $this->assertSame(0, $count->countSuivisPostCloture);
        $this->assertSame(0, $count->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore);
        $this->assertSame(0, $count->total());
    }

    public function testTotalIsSumOfAllProperties(): void
    {
        $count = new CountDossiersMessagesUsagers(
            countSuivisUsagersWithoutAskFeedbackBefore: 2,
            countSuivisPostCloture: 3,
            countSuivisUsagerOrPoursuiteWithAskFeedbackBefore: 5,
        );

        $this->assertSame(2, $count->countSuivisUsagersWithoutAskFeedbackBefore);
        $this->assertSame(3, $count->countSuivisPostCloture);
        $this->assertSame(5, $count->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore);
        $this->assertSame(10, $count->total());
    }

    public function testTotalWithDifferentValues(): void
    {
        $count = new CountDossiersMessagesUsagers(
            countSuivisUsagersWithoutAskFeedbackBefore: 7,
            countSuivisPostCloture: 1,
            countSuivisUsagerOrPoursuiteWithAskFeedbackBefore: 4,
        );

        $this->assertSame(12, $count->total());
    }
}
