<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\Profile;
use App\Entity\SignalementDraft;
use App\Utils\AddressParser;
use PHPUnit\Framework\TestCase;

class SignalementDraftTest extends TestCase
{
    public function testCreateSignalementDraft(): void
    {
        $signalementDraft = (new SignalementDraft())
            ->setAddress('17 quai de la joliette 13002 Marseille')
            ->setPayload(['adresse_logement_etage' => 2])
            ->setProfile(Profile::LOCATAIRE)
            ->setEmailDeclarant('john.doe@yopmail.com')
            ->setCurrentStep('3:zone_concernee');

        $this->assertNotNull($signalementDraft->getUuid());
        $this->assertNotEmpty($signalementDraft->getPayload());
        $this->assertNotEmpty($signalementDraft->getProfile());

        $addressParsed = AddressParser::parse($signalementDraft->getAddress());
        $this->assertEquals('17', $addressParsed['number']);
        $this->assertNull($addressParsed['suffix']);
        $this->assertEquals('Quai de la joliette 13002 Marseille', $addressParsed['street']);

        $this->assertEquals('john.doe@yopmail.com', $signalementDraft->getEmailDeclarant());
        $this->assertEquals('3:zone_concernee', $signalementDraft->getCurrentStep());
    }
}
