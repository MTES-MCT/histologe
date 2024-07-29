<?php

namespace App\Tests\Unit\Entity;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AutoAffectationRuleTest extends KernelTestCase
{
    use FixturesHelper;

    public function testDescriptionShort(): void
    {
        $autoAffectationRule = $this->getAutoAffectationRule();
        $this->assertEquals(PartnerType::CAF_MSA, $autoAffectationRule->getPartnerType());
        $this->assertEquals('Ain', $autoAffectationRule->getTerritory()->getName());
        $this->assertEquals('Règle d\'auto-affectation pour les partenaires CAF / MSA du territoire Ain', $autoAffectationRule->getDescription());
    }

    public function testDescriptionLong(): void
    {
        $autoAffectationRule = $this->getAutoAffectationRule();
        $this->assertEquals(PartnerType::CAF_MSA, $autoAffectationRule->getPartnerType());
        $this->assertEquals('Ain', $autoAffectationRule->getTerritory()->getName());
        $this->assertEquals('prive', $autoAffectationRule->getParc());
        $this->assertEquals('all', $autoAffectationRule->getProfileDeclarant());
        $this->assertEquals('oui', $autoAffectationRule->getAllocataire());
        $this->assertEquals('partner_list', $autoAffectationRule->getInseeToInclude());
        $this->assertNull($autoAffectationRule->getInseeToExclude());
        $this->assertEmpty($autoAffectationRule->getPartnerToExclude());
        $this->assertEquals(AutoAffectationRule::STATUS_ACTIVE, $autoAffectationRule->getStatus());
        $this->assertEquals('Règle d\'auto-affectation pour les partenaires CAF / MSA du territoire Ain concernant les logements du parc privé. Cette règle concerne les signalements faits par tous profils de déclarant. Elle concerne les foyers allocataires. Elle s\'applique aux logements situés dans le périmètre du partenaire (codes insee). (Règle active)', $autoAffectationRule->getDescription(false));
    }
}
