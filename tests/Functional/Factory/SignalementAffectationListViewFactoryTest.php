<?php

namespace App\Tests\Functional\Factory;

use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Factory\SignalementAffectationListViewFactory;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SignalementAffectationListViewFactoryTest extends KernelTestCase
{
    private ManagerRegistry $entityManager;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private Security $security;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine');
        $this->csrfTokenManager = self::getContainer()->get(CsrfTokenManagerInterface::class);
        $this->security = self::getContainer()->get(Security::class);
    }

    public function testCreateFactory(): void
    {
        $procedures = implode(',', [
            'NON_DECENCE',
            'RSD',
            'INSALUBRITE',
            'MISE_EN_SECURITE_PERIL',
            'LOGEMENT_DECENT',
            'RESPONSABILITE_OCCUPANT_ASSURANTIEL',
            'AUTRE',
        ]);

        $faker = Factory::create();
        $dataSignalement = [
            'id' => 1,
            'uuid' => '00000000-0000-0000-2022-000000000001',
            'reference' => '2022-1',
            'createdAt' => new \DateTimeImmutable(),
            'statut' => SignalementStatus::ACTIVE,
            'score' => 25,
            'isNotOccupant' => false,
            'nomOccupant' => $faker->lastName(),
            'prenomOccupant' => $faker->firstName(),
            'adresseOccupant' => $faker->streetAddress(),
            'villeOccupant' => $faker->city(),
            'cpOccupant' => $faker->postcode(),
            'lastSuiviAt' => new \DateTimeImmutable(),
            'lastSuiviBy' => $faker->name(),
            'lastSuiviIsPublic' => false,
            'profileDeclarant' => ProfileDeclarant::LOCATAIRE,
            'rawAffectations' => 'Partenaire 13-02||EN_COURS;Partenaire 13-03||EN_COURS;Partenaire 13-04||EN_COURS',
            'qualifications' => 'NON_DECENCE_ENERGETIQUE',
            'qualificationsStatuses' => 'NDE_AVEREE',
            'conclusionsProcedure' => $procedures,
            'territoryId' => 13,
        ];

        $expectedAffectations = [
            'Partenaire 13-02' => [
                'partner' => 'Partenaire 13-02',
                'statut' => 'EN_COURS',
            ],
            'Partenaire 13-03' => [
                'partner' => 'Partenaire 13-03',
                'statut' => 'EN_COURS',
            ],
            'Partenaire 13-04' => [
                'partner' => 'Partenaire 13-04',
                'statut' => 'EN_COURS',
            ],
        ];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $signalementAffectationListViewFactory = new SignalementAffectationListViewFactory($this->csrfTokenManager, $this->security);
        $signalementAffectationListView = $signalementAffectationListViewFactory->createInstanceFrom($user, $dataSignalement);
        $this->assertEquals($dataSignalement['id'], $signalementAffectationListView->getId());
        $this->assertEquals($dataSignalement['uuid'], $signalementAffectationListView->getUuid());
        $this->assertEquals($dataSignalement['reference'], $signalementAffectationListView->getReference());
        $this->assertSame($dataSignalement['createdAt'], $signalementAffectationListView->getCreatedAt());
        $this->assertEquals($dataSignalement['statut'], $signalementAffectationListView->getStatut());
        $this->assertEquals($dataSignalement['score'], $signalementAffectationListView->getScore());
        $this->assertEquals($dataSignalement['isNotOccupant'], $signalementAffectationListView->getIsNotOccupant());
        $this->assertEquals($dataSignalement['nomOccupant'], $signalementAffectationListView->getNomOccupant());
        $this->assertEquals($dataSignalement['prenomOccupant'], $signalementAffectationListView->getPrenomOccupant());
        $this->assertEquals($dataSignalement['adresseOccupant'], $signalementAffectationListView->getAdresseOccupant());
        $this->assertEquals($dataSignalement['villeOccupant'], $signalementAffectationListView->getVilleOccupant());
        $this->assertEquals($dataSignalement['cpOccupant'], $signalementAffectationListView->getCodepostalOccupant());
        $this->assertSame($dataSignalement['lastSuiviAt'], $signalementAffectationListView->getLastSuiviAt());
        $this->assertEquals($dataSignalement['lastSuiviBy'], $signalementAffectationListView->getLastSuiviBy());
        $this->assertEquals('Locataire', $signalementAffectationListView->getProfileDeclarant());
        $this->assertSame($expectedAffectations, $signalementAffectationListView->getAffectations());
        $this->assertTrue($signalementAffectationListView->hasNde());
        $this->assertEquals(
            $dataSignalement['lastSuiviIsPublic'],
            $signalementAffectationListView->getLastSuiviIsPublic());
        $this->assertSame(
            array_values(ProcedureType::getLabelList()),
            $signalementAffectationListView->getConclusionsProcedure());
        $this->assertFalse($signalementAffectationListView->getCanDeleteSignalement());
    }
}
