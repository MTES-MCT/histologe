<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\PartnerTypeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PartnerTypeSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(PartnerType $type, PartnerType $typeRule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $partner->setType($type);
        $this->assertEquals($type, $partner->getType());
        $signalement = new Signalement();

        $specification = new PartnerTypeSpecification($typeRule);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'ADIL - ADIL' => [PartnerType::ADIL, PartnerType::ADIL, true];
        yield 'ADIL - EPCI' => [PartnerType::ADIL, PartnerType::EPCI, false];

        yield 'ARS - ARS' => [PartnerType::ARS, PartnerType::ARS, true];
        yield 'ARS - EPCI' => [PartnerType::ARS, PartnerType::EPCI, false];
        yield 'ASSOCIATION - ASSOCIATION' => [PartnerType::ASSOCIATION, PartnerType::ASSOCIATION, true];
        yield 'ASSOCIATION - EPCI' => [PartnerType::ASSOCIATION, PartnerType::EPCI, false];
        yield 'BAILLEUR_SOCIAL - BAILLEUR_SOCIAL' => [PartnerType::BAILLEUR_SOCIAL, PartnerType::BAILLEUR_SOCIAL, true];
        yield 'BAILLEUR_SOCIAL - EPCI' => [PartnerType::BAILLEUR_SOCIAL, PartnerType::EPCI, false];
        yield 'CAF_MSA - CAF_MSA' => [PartnerType::CAF_MSA, PartnerType::CAF_MSA, true];
        yield 'CAF_MSA - EPCI' => [PartnerType::CAF_MSA, PartnerType::EPCI, false];
        yield 'CCAS - CCAS' => [PartnerType::CCAS, PartnerType::CCAS, true];
        yield 'CCAS - EPCI' => [PartnerType::CCAS, PartnerType::EPCI, false];
        yield 'COMMUNE_SCHS - COMMUNE_SCHS' => [PartnerType::COMMUNE_SCHS, PartnerType::COMMUNE_SCHS, true];
        yield 'COMMUNE_SCHS - EPCI' => [PartnerType::COMMUNE_SCHS, PartnerType::EPCI, false];
        yield 'CONCILIATEURS - CONCILIATEURS' => [PartnerType::CONCILIATEURS, PartnerType::CONCILIATEURS, true];
        yield 'CONCILIATEURS - EPCI' => [PartnerType::CONCILIATEURS, PartnerType::EPCI, false];
        yield 'CONSEIL_DEPARTEMENTAL - CONSEIL_DEPARTEMENTAL' => [PartnerType::CONSEIL_DEPARTEMENTAL, PartnerType::CONSEIL_DEPARTEMENTAL, true];
        yield 'CONSEIL_DEPARTEMENTAL - EPCI' => [PartnerType::CONSEIL_DEPARTEMENTAL, PartnerType::EPCI, false];
        yield 'DDETS - DDETS' => [PartnerType::DDETS, PartnerType::DDETS, true];
        yield 'DDETS - EPCI' => [PartnerType::DDETS, PartnerType::EPCI, false];
        yield 'DDT_M - DDT_M' => [PartnerType::DDT_M, PartnerType::DDT_M, true];
        yield 'DDT_M - EPCI' => [PartnerType::DDT_M, PartnerType::EPCI, false];
        yield 'DISPOSITIF_RENOVATION_HABITAT - DISPOSITIF_RENOVATION_HABITAT' => [PartnerType::DISPOSITIF_RENOVATION_HABITAT, PartnerType::DISPOSITIF_RENOVATION_HABITAT, true];
        yield 'DISPOSITIF_RENOVATION_HABITAT - EPCI' => [PartnerType::DISPOSITIF_RENOVATION_HABITAT, PartnerType::EPCI, false];
        yield 'EPCI - EPCI' => [PartnerType::EPCI, PartnerType::EPCI, true];
        yield 'EPCI - DISPOSITIF_RENOVATION_HABITAT' => [PartnerType::EPCI, PartnerType::DISPOSITIF_RENOVATION_HABITAT, false];
        yield 'OPERATEUR_VISITES_ET_TRAVAUX - OPERATEUR_VISITES_ET_TRAVAUX' => [PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, true];
        yield 'OPERATEUR_VISITES_ET_TRAVAUX - EPCI' => [PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, PartnerType::EPCI, false];
        yield 'POLICE_GENDARMERIE - POLICE_GENDARMERIE' => [PartnerType::POLICE_GENDARMERIE, PartnerType::POLICE_GENDARMERIE, true];
        yield 'POLICE_GENDARMERIE - EPCI' => [PartnerType::POLICE_GENDARMERIE, PartnerType::EPCI, false];
        yield 'PREFECTURE - PREFECTURE' => [PartnerType::PREFECTURE, PartnerType::PREFECTURE, true];
        yield 'PREFECTURE - EPCI' => [PartnerType::PREFECTURE, PartnerType::EPCI, false];
        yield 'TRIBUNAL - TRIBUNAL' => [PartnerType::TRIBUNAL, PartnerType::TRIBUNAL, true];
        yield 'TRIBUNAL - EPCI' => [PartnerType::TRIBUNAL, PartnerType::EPCI, false];
        yield 'AUTRE - AUTRE' => [PartnerType::AUTRE, PartnerType::AUTRE, true];
        yield 'AUTRE - EPCI' => [PartnerType::AUTRE, PartnerType::EPCI, false];
    }
}
