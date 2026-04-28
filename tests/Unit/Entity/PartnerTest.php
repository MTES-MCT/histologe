<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
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

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEmail($faker->companyEmail())
            ->setIsArchive(false)
            ->setTerritory($territory)
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken((string) $faker->randomKey())
            ->setType(PartnerType::ADIL);

        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($partner);
        $this->assertEquals(0, $errors->count());
    }

    /**
     * @dataProvider provideDataForTestPartnerWithEmail
     */
    public function testCreatePartnerNoValidWithEmailExistInTerritory(int $zip, int $countErrors): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $territory = $entityManager->getRepository(Territory::class)->find($zip);
        $partner = (new Partner())
            ->setNom('Random partner')
            ->setEmail('partenaire-13-01@signal-logement.fr')
            ->setType(PartnerType::COMMUNE_SCHS)
            ->setCompetence([Qualification::VISITES])
            ->setTerritory($territory);

        $validator = self::getContainer()->get('validator');
        $errors = $validator->validate($partner);
        $this->assertEquals($countErrors, $errors->count());
    }

    public static function provideDataForTestPartnerWithEmail(): \Generator
    {
        yield 'Create partner not valid with email exists in territory' => [13, 1];

        yield 'Create partner valid with email exists in territory' => [1, 0];
    }
}
