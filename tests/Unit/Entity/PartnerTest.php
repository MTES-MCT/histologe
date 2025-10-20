<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class PartnerTest extends KernelTestCase
{
    use FixturesHelper;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @dataProvider provideMailData
     */
    public function testPartnerHasEmailIssue(string $email, bool $hasIssue): void
    {
        /** @var Partner $partner */
        $partner = $this->entityManager->getRepository(Partner::class)->findOneBy(['email' => $email]);

        $this->assertNotNull($partner->getEmailDeliveryIssue());
        $this->assertEquals($hasIssue, $partner->hasEmailIssue());
    }

    public function provideMailData(): \Generator
    {
        yield 'Partner KO with user OK' => ['partenaire-13-01@signal-logement.fr', false];
        yield 'Partner KO with user KO' => ['partenaire-01-04@signal-logement.fr', true];
    }

    public function testPartnerWithUserIsValid(): void
    {
        $validator = self::getContainer()->get('validator');
        $territory = $this->entityManager->getRepository(Territory::class)->find(1);
        $faker = Factory::create();

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEmail($faker->companyEmail())
            ->setIsArchive(false)
            ->setTerritory($territory)
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->randomKey())
            ->setType(PartnerType::ADIL);

        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($partner);
        $this->assertEquals(0, $errors->count());
    }

    /**
     * @dataProvider provideDataSyncOilhi
     */
    public function testPartnerSCHSCompetenceRSDWithSpecificInseeCanSyncWithOilhi(
        string $zip,
        string $territoryName,
        string $insee,
        string $partnerName,
    ): void {
        $territory = $this->getTerritory($territoryName)->setZip($zip);
        $signalement = $this->getSignalement($territory);
        $signalement->setInseeOccupant($insee);
        $partner = (new Partner())
            ->setNom($partnerName)
            ->setType(PartnerType::COMMUNE_SCHS)
            ->setCompetence([Qualification::RSD])
            ->setInsee([(int) $insee])
            ->setTerritory($territory);

        $this->assertTrue($partner->canSyncWithOilhi($signalement));
    }

    public function provideDataSyncOilhi(): \Generator
    {
        yield 'Code insee 62091' => ['zip' => '62', 'Pas-de-Calais', '62091', 'BEAUDRICOURT'];
        yield 'Code insee 55502' => ['zip' => '55', 'Meuse', '55502', 'STENAY'];
        yield 'Code insee 55029' => ['zip' => '55', 'Meuse', '55029', 'BAR-LE-DUC'];
        yield 'Code insee 55545' => ['zip' => '55', 'Meuse', '55545', 'VERDUN'];
    }

    /**
     * @dataProvider provideDataForTestPartnerWithEmail
     */
    public function testCreatePartnerNoValidWithEmailExistInTerritory(int $zip, int $countErrors): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->find($zip);
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

    public function provideDataForTestPartnerWithEmail(): \Generator
    {
        yield 'Create partner not valid with email exists in territory' => [13, 1];
        yield 'Create partner valid with email exists in territory' => [1, 0];
    }
}
