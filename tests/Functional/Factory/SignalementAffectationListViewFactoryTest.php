<?php

namespace App\Tests\Functional\Factory;

use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Factory\SignalementAffectationListViewFactory;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementAffectationListViewFactoryTest extends KernelTestCase
{
    private ManagerRegistry $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine');
    }

    public function testCreateFactory(): void
    {
        $faker = Factory::create();
        $dataSignalement = [
            'id' => 1,
            'uuid' => '00000000-0000-0000-2022-000000000001',
            'reference' => '2022-1',
            'createdAt' => new \DateTimeImmutable(),
            'statut' => SignalementStatus::ACTIVE->value,
            'scoreCreation' => 100,
            'newScoreCreation' => 25,
            'isNotOccupant' => false,
            'nomOccupant' => $faker->lastName(),
            'prenomOccupant' => $faker->firstName(),
            'adresseOccupant' => $faker->streetAddress(),
            'villeOccupant' => $faker->city(),
            'lastSuiviAt' => new \DateTimeImmutable(),
            'lastSuiviBy' => $faker->name(),
            'rawAffectations' => 'Partenaire 13-02||1;Partenaire 13-03||1;Partenaire 13-04||1',
            'qualifications' => null,
        ];

        $expectedAffectations = [
            'Partenaire 13-02' => [
                'partner' => 'Partenaire 13-02',
                'statut' => 1,
            ],
            'Partenaire 13-03' => [
                'partner' => 'Partenaire 13-03',
                'statut' => 1,
            ],
            'Partenaire 13-04' => [
                'partner' => 'Partenaire 13-04',
                'statut' => 1,
            ],
        ];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@histologe.fr']);

        $signalementAffectationListViewFactory = new SignalementAffectationListViewFactory();
        $signalementAffectationListView = $signalementAffectationListViewFactory->createInstanceFrom($user, $dataSignalement);

        $this->assertEquals($dataSignalement['id'], $signalementAffectationListView->getId());
        $this->assertEquals($dataSignalement['uuid'], $signalementAffectationListView->getUuid());
        $this->assertEquals($dataSignalement['reference'], $signalementAffectationListView->getReference());
        $this->assertSame($dataSignalement['createdAt'], $signalementAffectationListView->getCreatedAt());
        $this->assertEquals($dataSignalement['statut'], $signalementAffectationListView->getStatut());
        $this->assertEquals($dataSignalement['scoreCreation'], $signalementAffectationListView->getScoreCreation());
        $this->assertEquals($dataSignalement['newScoreCreation'], $signalementAffectationListView->getNewScoreCreation());
        $this->assertEquals($dataSignalement['isNotOccupant'], $signalementAffectationListView->getIsNotOccupant());
        $this->assertEquals($dataSignalement['nomOccupant'], $signalementAffectationListView->getNomOccupant());
        $this->assertEquals($dataSignalement['prenomOccupant'], $signalementAffectationListView->getPrenomOccupant());
        $this->assertEquals($dataSignalement['adresseOccupant'], $signalementAffectationListView->getAdresseOccupant());
        $this->assertEquals($dataSignalement['villeOccupant'], $signalementAffectationListView->getVilleOccupant());
        $this->assertSame($dataSignalement['lastSuiviAt'], $signalementAffectationListView->getLastSuiviAt());
        $this->assertEquals($dataSignalement['lastSuiviBy'], $signalementAffectationListView->getLastSuiviBy());
        $this->assertSame($expectedAffectations, $signalementAffectationListView->getAffectations());
    }
}
