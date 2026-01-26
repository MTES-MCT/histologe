<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Service\Signalement\SignalementProfileOccupantMapper;
use PHPUnit\Framework\TestCase;

class SignalementProfileOccupantMapperTest extends TestCase
{
    /**
     * @dataProvider provideInputValue
     */
    public function testMap(string $profileOccupantInput, ?ProfileDeclarant $profileDeclarant, ?ProfileOccupant $profileOccupant): void
    {
        $this->assertEquals($profileOccupant, SignalementProfileOccupantMapper::map($profileOccupantInput, $profileDeclarant));
    }

    public function provideInputValue(): \Generator
    {
        yield 'Occupant empty, Declarant locataire' => ['', ProfileDeclarant::LOCATAIRE, ProfileOccupant::LOCATAIRE];
        yield 'Occupant empty, Declarant bailleur' => ['', ProfileDeclarant::BAILLEUR, ProfileOccupant::LOCATAIRE];
        yield 'Occupant empty, Declarant bailleur occupant' => ['', ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileOccupant::BAILLEUR_OCCUPANT];
        yield 'Occupant locataire, Declarant tiers particulier' => [ProfileOccupant::LOCATAIRE->value, ProfileDeclarant::TIERS_PARTICULIER, ProfileOccupant::LOCATAIRE];
        yield 'Occupant bailleur occupant, Declarant tiers pro' => [ProfileOccupant::BAILLEUR_OCCUPANT->value, ProfileDeclarant::TIERS_PRO, ProfileOccupant::BAILLEUR_OCCUPANT];
        yield 'Occupant empty, Declarant service secours' => ['', ProfileDeclarant::SERVICE_SECOURS, null];
    }
}
