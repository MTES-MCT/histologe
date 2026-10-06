<?php

namespace App\Tests\Unit\Entity;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $this->assertEmpty($autoAffectationRule->getInseeToInclude());
        $this->assertNull($autoAffectationRule->getInseeToExclude());
        $this->assertEmpty($autoAffectationRule->getPartnerToExclude());
        $this->assertEquals(AutoAffectationRule::STATUS_ACTIVE, $autoAffectationRule->getStatus());
        $this->assertEquals('Règle d\'auto-affectation pour les partenaires CAF / MSA du territoire Ain concernant les logements du parc privé. Cette règle concerne les signalements faits par tous profils de déclarant. Elle concerne les foyers allocataires. Elle s\'applique aux logements situés dans le périmètre géographique du partenaire (codes insee et/ou zones). (Règle active)', $autoAffectationRule->getDescription(false));
    }

    public function testDescriptionLongWithZones(): void
    {
        $autoAffectationRule = $this->getAutoAffectationRule()
            ->setZoneToInclude(['12', '13'])
            ->setZoneToExclude(['14']);
        $this->assertStringContainsString(
            '(codes insee et/ou zones), limités aux zones suivantes : 12,13 à l\'exclusion des logements situés dans les zones suivantes : 14. (Règle active)',
            $autoAffectationRule->getDescription(false)
        );
    }

    public function testDescriptionLongWithTravailleurSocialAndDemandeLogementSocial(): void
    {
        $autoAffectationRule = $this->getAutoAffectationRule()
            ->setAccompagnementTravailleurSocial('oui')
            ->setDemandeLogementSocial('non');
        $this->assertEquals('Règle d\'auto-affectation pour les partenaires CAF / MSA du territoire Ain concernant les logements du parc privé. Cette règle concerne les signalements faits par tous profils de déclarant. Elle concerne les foyers allocataires. Elle concerne les foyers accompagnés par un travailleur social. Elle concerne les foyers n\'ayant pas fait de demande de logement social. Elle s\'applique aux logements situés dans le périmètre géographique du partenaire (codes insee et/ou zones). (Règle active)', $autoAffectationRule->getDescription(false));
    }

    public function testValidateZonesNotIncludedAndExcluded(): void
    {
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $autoAffectationRule = $this->getAutoAffectationRule()
            ->setZoneToInclude(['12', '13'])
            ->setZoneToExclude(['14']);
        $this->assertCount(0, $validator->validate($autoAffectationRule, new Callback('validateZonesNotIncludedAndExcluded')));

        $autoAffectationRule->setZoneToExclude(['13', '14']);
        $violations = $validator->validate($autoAffectationRule, new Callback('validateZonesNotIncludedAndExcluded'));
        $this->assertCount(1, $violations);
        $this->assertSame('La zone ID 13 ne peut pas être à la fois incluse et exclue.', $violations[0]->getMessage());
        $this->assertSame('zoneToExclude', $violations[0]->getPropertyPath());
    }
}
