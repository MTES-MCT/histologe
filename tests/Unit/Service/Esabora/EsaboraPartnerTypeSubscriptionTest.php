<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\PartnerType;
use App\Service\Esabora\EsaboraPartnerTypeSubscription;
use PHPUnit\Framework\TestCase;

class EsaboraPartnerTypeSubscriptionTest extends TestCase
{
    /**
     * @dataProvider providePartnerType
     */
    public function testIsSubscribed(PartnerType $partnerType, bool $isSubscribed): void
    {
        $esaboraPartnerTypeSubscription = new EsaboraPartnerTypeSubscription();
        $this->assertEquals($isSubscribed, $esaboraPartnerTypeSubscription->isSubscribed($partnerType));
    }

    public function providePartnerType(): \Generator
    {
        yield 'ADIL' => [PartnerType::ADIL, false];
        yield 'ARS' => [PartnerType::ARS, true];
        yield 'ASSOCIATION' => [PartnerType::ASSOCIATION, false];
        yield 'BAILLEUR_SOCIAL' => [PartnerType::BAILLEUR_SOCIAL, false];
        yield 'CAF_MSA' => [PartnerType::CAF_MSA, false];
        yield 'CCAS' => [PartnerType::CCAS, false];
        yield 'COMMUNE_SCHS' => [PartnerType::COMMUNE_SCHS, true];
        yield 'CONCILIATEURS' => [PartnerType::CONCILIATEURS, false];
        yield 'CONSEIL_DEPARTEMENTAL' => [PartnerType::CONSEIL_DEPARTEMENTAL, false];
        yield 'DDETS' => [PartnerType::DDETS, false];
        yield 'DDT_M' => [PartnerType::DDT_M, false];
        yield 'DISPOSITIF_RENOVATION_HABITAT' => [PartnerType::DISPOSITIF_RENOVATION_HABITAT, false];
        yield 'EPCI' => [PartnerType::EPCI, false];
        yield 'OPERATEUR_VISITES_ET_TRAVAUX' => [PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, false];
        yield 'POLICE_GENDARMERIE' => [PartnerType::POLICE_GENDARMERIE, false];
        yield 'PREFECTURE' => [PartnerType::PREFECTURE, false];
        yield 'TRIBUNAL' => [PartnerType::TRIBUNAL, false];
        yield 'AUTRE' => [PartnerType::AUTRE, false];
    }
}
