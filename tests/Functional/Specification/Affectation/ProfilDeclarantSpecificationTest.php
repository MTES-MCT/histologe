<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Specification\Affectation\ProfilDeclarantSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProfilDeclarantSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(ProfileDeclarant $profilSignalement, string $profilRule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        $signalement->setCreatedFrom(new SignalementDraft());
        $signalement->setProfileDeclarant($profilSignalement);
        $retrievedProfileDeclarant = $signalement->getProfileDeclarant();

        $this->assertEquals($profilSignalement, $retrievedProfileDeclarant);
        $specification = new ProfilDeclarantSpecification($profilRule);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'all - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, 'all', true];
        yield 'all - BAILLEUR' => [ProfileDeclarant::BAILLEUR, 'all', true];
        yield 'all - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, 'all', true];
        yield 'all - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, 'all', true];
        yield 'all - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, 'all', true];
        yield 'all - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, 'all', true];
        yield 'tiers - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, 'tiers', false];
        yield 'tiers - BAILLEUR' => [ProfileDeclarant::BAILLEUR, 'tiers', true];
        yield 'tiers - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, 'tiers', false];
        yield 'tiers - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, 'tiers', true];
        yield 'tiers - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, 'tiers', true];
        yield 'tiers - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, 'tiers', true];
        yield 'occupant - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, 'occupant', true];
        yield 'occupant - BAILLEUR' => [ProfileDeclarant::BAILLEUR, 'occupant', false];
        yield 'occupant - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, 'occupant', true];
        yield 'occupant - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, 'occupant', false];
        yield 'occupant - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, 'occupant', false];
        yield 'occupant - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, 'occupant', false];

        yield 'LOCATAIRE - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::LOCATAIRE->value, true];
        yield 'LOCATAIRE - BAILLEUR' => [ProfileDeclarant::BAILLEUR, ProfileDeclarant::LOCATAIRE->value, false];
        yield 'LOCATAIRE - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileDeclarant::LOCATAIRE->value, false];
        yield 'LOCATAIRE - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, ProfileDeclarant::LOCATAIRE->value, false];
        yield 'LOCATAIRE - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileDeclarant::LOCATAIRE->value, false];
        yield 'LOCATAIRE - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, ProfileDeclarant::LOCATAIRE->value, false];

        yield 'BAILLEUR - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::BAILLEUR->value, false];
        yield 'BAILLEUR - BAILLEUR' => [ProfileDeclarant::BAILLEUR, ProfileDeclarant::BAILLEUR->value, true];
        yield 'BAILLEUR - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileDeclarant::BAILLEUR->value, false];
        yield 'BAILLEUR - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, ProfileDeclarant::BAILLEUR->value, false];
        yield 'BAILLEUR - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileDeclarant::BAILLEUR->value, false];
        yield 'BAILLEUR - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, ProfileDeclarant::BAILLEUR->value, false];

        yield 'BAILLEUR_OCCUPANT - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::BAILLEUR_OCCUPANT->value, false];
        yield 'BAILLEUR_OCCUPANT - BAILLEUR' => [ProfileDeclarant::BAILLEUR, ProfileDeclarant::BAILLEUR_OCCUPANT->value, false];
        yield 'BAILLEUR_OCCUPANT - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileDeclarant::BAILLEUR_OCCUPANT->value, true];
        yield 'BAILLEUR_OCCUPANT - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, ProfileDeclarant::BAILLEUR_OCCUPANT->value, false];
        yield 'BAILLEUR_OCCUPANT - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileDeclarant::BAILLEUR_OCCUPANT->value, false];
        yield 'BAILLEUR_OCCUPANT - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, ProfileDeclarant::BAILLEUR_OCCUPANT->value, false];

        yield 'SERVICE_SECOURS - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::SERVICE_SECOURS->value, false];
        yield 'SERVICE_SECOURS - BAILLEUR' => [ProfileDeclarant::BAILLEUR, ProfileDeclarant::SERVICE_SECOURS->value, false];
        yield 'SERVICE_SECOURS - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileDeclarant::SERVICE_SECOURS->value, false];
        yield 'SERVICE_SECOURS - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, ProfileDeclarant::SERVICE_SECOURS->value, true];
        yield 'SERVICE_SECOURS - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileDeclarant::SERVICE_SECOURS->value, false];
        yield 'SERVICE_SECOURS - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, ProfileDeclarant::SERVICE_SECOURS->value, false];

        yield 'TIERS_PARTICULIER - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::TIERS_PARTICULIER->value, false];
        yield 'TIERS_PARTICULIER - BAILLEUR' => [ProfileDeclarant::BAILLEUR, ProfileDeclarant::TIERS_PARTICULIER->value, false];
        yield 'TIERS_PARTICULIER - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileDeclarant::TIERS_PARTICULIER->value, false];
        yield 'TIERS_PARTICULIER - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, ProfileDeclarant::TIERS_PARTICULIER->value, false];
        yield 'TIERS_PARTICULIER - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileDeclarant::TIERS_PARTICULIER->value, true];
        yield 'TIERS_PARTICULIER - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, ProfileDeclarant::TIERS_PARTICULIER->value, false];

        yield 'TIERS_PRO - LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, ProfileDeclarant::TIERS_PRO->value, false];
        yield 'TIERS_PRO - BAILLEUR' => [ProfileDeclarant::BAILLEUR, ProfileDeclarant::TIERS_PRO->value, false];
        yield 'TIERS_PRO - BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, ProfileDeclarant::TIERS_PRO->value, false];
        yield 'TIERS_PRO - SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS, ProfileDeclarant::TIERS_PRO->value, false];
        yield 'TIERS_PRO - TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileDeclarant::TIERS_PRO->value, false];
        yield 'TIERS_PRO - TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, ProfileDeclarant::TIERS_PRO->value, true];
    }
}
