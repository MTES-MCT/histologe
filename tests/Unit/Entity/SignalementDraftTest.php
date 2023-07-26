<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use App\Utils\AddressParser;
use PHPUnit\Framework\TestCase;

class SignalementDraftTest extends TestCase
{
    public function testCreateSignalementDraft(): void
    {
        $signalementDraft = (new SignalementDraft())
            ->setAddressComplete('17 quai de la joliette 13002 Marseille')
            ->setPayload(['adresse_logement_etage' => 2])
            ->setProfileDeclarant(ProfileDeclarant::LOCATAIRE)
            ->setEmailDeclarant('john.doe@yopmail.com')
            ->setCurrentStep('3:zone_concernee');

        $this->assertNotNull($signalementDraft->getUuid());
        $this->assertNotEmpty($signalementDraft->getPayload());
        $this->assertNotEmpty($signalementDraft->getProfileDeclarant());

        $addressParsed = AddressParser::parse($signalementDraft->getAddressComplete());
        $this->assertEquals('17', $addressParsed['number']);
        $this->assertNull($addressParsed['suffix']);
        $this->assertEquals('Quai de la joliette 13002 Marseille', $addressParsed['street']);

        $this->assertEquals('john.doe@yopmail.com', $signalementDraft->getEmailDeclarant());
        $this->assertEquals('3:zone_concernee', $signalementDraft->getCurrentStep());
        $this->assertEquals(SignalementDraftStatus::EN_COURS, $signalementDraft->getStatus());
    }
}
