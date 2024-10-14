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
            ->setRoles([User::ROLES['Utilisateur']])
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
                ->setRoles([User::ROLES['Utilisateur']])
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

    public function testCreatePartnerNoValidWithEmailExistInTerritory(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $territory = $entityManager->getRepository(Territory::class)->find(13);
        $partner = (new Partner())
            ->setNom('Random partner')
            ->setEmail('partenaire-13-01@histologe.fr')
            ->setType(PartnerType::COMMUNE_SCHS)
            ->setCompetence([Qualification::VISITES])
            ->setTerritory($territory);

        $validator = self::getContainer()->get('validator');
        $errors = $validator->validate($partner);
        $this->assertEquals(1, $errors->count());
    }

    public function testCreatePartnerValidWithEmailExistInTerritory(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $territory = $entityManager->getRepository(Territory::class)->find(1);
        $partner = (new Partner())
            ->setNom('Random partner')
            ->setEmail('partenaire-13-01@histologe.fr')
            ->setType(PartnerType::COMMUNE_SCHS)
            ->setCompetence([Qualification::VISITES])
            ->setTerritory($territory);

        $validator = self::getContainer()->get('validator');
        $errors = $validator->validate($partner);
        $this->assertEquals(0, $errors->count());
    }
}
