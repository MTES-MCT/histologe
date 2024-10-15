<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Tests\FixturesHelper;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class PartnerTest extends KernelTestCase
{
    use FixturesHelper;

    public function testPartnerWithUserIsValid(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $validator = self::getContainer()->get('validator');

        $territory = $entityManager->getRepository(Territory::class)->find(1);
        $faker = Factory::create();

        $user = (new User())
            ->setEmail($faker->email())
            ->setNom($faker->lastName())
            ->setPrenom($faker->firstName())
            ->setRoles([User::ROLES['Agent']])
            ->setIsMailingActive(true)
            ->setPassword($faker->password());

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEmail($faker->companyEmail())
            ->setIsArchive(false)
            ->setTerritory($territory)
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->randomKey())
            ->setType(PartnerType::ADIL);

        for ($i = 0; $i < 3; ++$i) {
            $user = (new User())
                ->setEmail($faker->email())
                ->setNom($faker->lastName())
                ->setPrenom($faker->firstName())
                ->setRoles([User::ROLES['Agent']])
                ->setIsMailingActive(true)
                ->setPassword($faker->password(8));
            $partner->addUser($user);
        }

        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($partner);
        $this->assertEquals(0, $errors->count());
    }

    public function testPartnerSCHSCompetenceRSDWithSpecificInseeCanSyncWithOilhi(): void
    {
        $territory = $this->getTerritory('Pas-de-calais')->setZip('62');
        $signalement = $this->getSignalement($territory);
        $signalement->setInseeOccupant('62091');
        $partner = (new Partner())
            ->setNom('BEAUDRICOURT')
            ->setType(PartnerType::COMMUNE_SCHS)
            ->setCompetence([Qualification::RSD])
            ->setInsee([62091])
            ->setTerritory($territory);

        $this->assertTrue($partner->canSyncWithOilhi($signalement));
    }
}
