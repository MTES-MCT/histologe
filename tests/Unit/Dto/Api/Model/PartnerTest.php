<?php

namespace App\Tests\Unit\Dto\Api\Model;

use App\Dto\Api\Model\Partner;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class PartnerTest extends TestCase
{
    use FixturesHelper;

    public function testCodeDepartementWithTerritoire(): void
    {
        $partner = $this->getPartner();
        $dto = new Partner($partner);

        $this->assertSame('01', $dto->codeDepartement);
    }

    public function testCodeDepartementWithoutTerritoire(): void
    {
        $partner = $this->getPartner(isOperatorExterne: true);

        $dto = new Partner($partner);

        $this->assertNull($dto->codeDepartement);
    }
}
