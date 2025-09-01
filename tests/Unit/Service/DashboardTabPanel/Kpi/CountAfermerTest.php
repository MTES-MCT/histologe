<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use PHPUnit\Framework\TestCase;

class CountAfermerTest extends TestCase
{
    public function testDefaultValuesAreZero(): void
    {
        $count = new CountAfermer();

        $this->assertSame(0, $count->countDemandesFermetureByUsager);
        $this->assertSame(0, $count->countDossiersRelanceSansReponse);
        $this->assertSame(0, $count->countDossiersFermePartenaireTous);
        $this->assertSame(0, $count->total());
    }

    public function testTotalIsSumOfAllProperties(): void
    {
        $count = new CountAfermer(
            countDemandesFermetureByUsager: 2,
            countDossiersRelanceSansReponse: 3,
            countDossiersFermePartenaireTous: 5,
        );

        $this->assertSame(2, $count->countDemandesFermetureByUsager);
        $this->assertSame(3, $count->countDossiersRelanceSansReponse);
        $this->assertSame(5, $count->countDossiersFermePartenaireTous);
        $this->assertSame(10, $count->total());
    }

    public function testTotalWithDifferentValues(): void
    {
        $count = new CountAfermer(
            countDemandesFermetureByUsager: 7,
            countDossiersRelanceSansReponse: 1,
            countDossiersFermePartenaireTous: 4,
        );

        $this->assertSame(12, $count->total());
    }
}
