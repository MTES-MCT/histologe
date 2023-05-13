<?php

namespace App\Tests;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Signalement;
use Faker\Factory;

trait FixturesHelper
{
    public function getAffectationEsabora(PartnerType $partnerType): Affectation
    {
        $faker = Factory::create();

        return (new Affectation())
            ->setPartner(
                (new Partner())
                    ->setEsaboraToken($faker->password(20))
                    ->setEsaboraUrl($faker->url())
                    ->setType($partnerType)
            )->setSignalement(
                (new Signalement())
                    ->setUuid($faker->uuid())
            );
    }
}
