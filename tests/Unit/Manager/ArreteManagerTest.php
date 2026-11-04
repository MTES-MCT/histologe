<?php

namespace App\Tests\Unit\Manager;

use App\Entity\Arrete;
use App\Entity\Enum\ArreteType;
use App\Entity\Enum\PartnerType;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\ArreteFactory;
use App\Factory\NotificationFactory;
use App\Manager\ArreteManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\ArreteRepository;
use App\Repository\UserRepository;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ArreteManagerTest extends TestCase
{
    use FixturesHelper;

    private const string ESABORA_TYPE_LABEL = 'Arrêté L.511-19 - Insalubrité';

    private MockObject&SuiviManager $suiviManager;
    private MockObject&UserManager $userManager;
    private MockObject&UserRepository $userRepository;
    private ArreteManager $arreteManager;
    private ?string $suiviDescription = null;

    protected function setUp(): void
    {
        $this->suiviDescription = null;
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->userManager->method('getSystemUser')->willReturn($this->getUser([User::ROLE_ADMIN]));
        $this->userRepository->method('findUsersSubscribedToSignalement')->willReturn([]);
        $this->suiviManager
            ->method('createSuivi')
            ->willReturnCallback(function ($signalement, string $description): Suivi {
                $this->suiviDescription = $description;

                return new Suivi();
            });

        $this->arreteManager = new ArreteManager(
            $this->createMock(ArreteRepository::class),
            $this->userRepository,
            $this->userManager,
            $this->suiviManager,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(NotificationFactory::class),
            $this->createMock(ArreteFactory::class),
            $this->createMock(NotificationMailerRegistry::class),
            $this->createMock(SignalementQualificationUpdater::class),
        );
    }

    public function testUpdateWithNewNumeroKeepsExistingMainLevee(): void
    {
        $arrete = $this->createArrete(['arrete_numero' => 'AP45OL022', 'arrete_mainlevee_numero' => 'ML001'], '01/01/2024');

        // Nouvel arrêté mais mainlevée nulle (synchronisation splitée)
        $result = $this->update($arrete, numero: 'AP45OL023', mainLeveeDate: null, mainLeveeNumero: null);

        $this->assertTrue($result);
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertEquals('AP45OL023', $additionalInformation['arrete_numero']);
        // Les informations de mainlevée sont préservées même si des valeurs nulles ont été envoyées
        $this->assertEquals('01/01/2024', $arrete->getDateMainLevee()?->format('d/m/Y'));
        $this->assertEquals('ML001', $additionalInformation['arrete_mainlevee_numero']);
    }

    public function testUpdateWithNumeroTransitionToNull(): void
    {
        $arrete = $this->createArrete(['arrete_numero' => 'AP45OL023', 'arrete_mainlevee_numero' => 'ML001'], '01/01/2024');

        $result = $this->update($arrete, numero: null);

        $this->assertTrue($result);
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertNull($additionalInformation['arrete_numero']);
        $this->assertEquals('01/01/2024', $arrete->getDateMainLevee()?->format('d/m/Y'));
        $this->assertEquals('ML001', $additionalInformation['arrete_mainlevee_numero']);
    }

    public function testUpdateWithNumeroTransitionFromNull(): void
    {
        $arrete = $this->createArrete(['arrete_numero' => null, 'arrete_mainlevee_numero' => 'ML001'], '01/01/2024');

        $result = $this->update($arrete, numero: 'AP45OL023');

        $this->assertTrue($result);
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertEquals('AP45OL023', $additionalInformation['arrete_numero']);
        $this->assertEquals('01/01/2024', $arrete->getDateMainLevee()?->format('d/m/Y'));
        $this->assertEquals('ML001', $additionalInformation['arrete_mainlevee_numero']);
    }

    public function testUpdateWithMainLeveeNumeroTransitionToNull(): void
    {
        $arrete = $this->createArrete(['arrete_numero' => 'AP45OL023', 'arrete_mainlevee_numero' => 'APML45K09O'], '01/01/2024');

        // arrete_mainlevee_numero passe à null (avec une date de mainlevée présente)
        $result = $this->update($arrete, mainLeveeDate: '01/01/2024', mainLeveeNumero: null);

        $this->assertTrue($result);
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertEquals('AP45OL023', $additionalInformation['arrete_numero']);
        $this->assertEquals('01/01/2024', $arrete->getDateMainLevee()?->format('d/m/Y'));
        $this->assertNull($additionalInformation['arrete_mainlevee_numero']);
    }

    public function testUpdateWithMainLeveeNumeroTransitionFromNull(): void
    {
        $arrete = $this->createArrete(['arrete_numero' => 'AP45OL023', 'arrete_mainlevee_numero' => null], '01/01/2024');

        $result = $this->update($arrete, mainLeveeDate: '01/01/2024', mainLeveeNumero: 'APML45K09O');

        $this->assertTrue($result);
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertEquals('AP45OL023', $additionalInformation['arrete_numero']);
        $this->assertEquals('01/01/2024', $arrete->getDateMainLevee()?->format('d/m/Y'));
        $this->assertEquals('APML45K09O', $additionalInformation['arrete_mainlevee_numero']);
    }

    public function testUpdateDetectsNewArreteModificatif(): void
    {
        $arrete = $this->createArrete(['arrete_numero' => 'AP45OL023']);

        // apparition d'un nouvel arrêté modificatif
        $result = $this->update($arrete, modificatifDate: '01/07/2024', modificatifNumero: 'AM001');

        $this->assertTrue($result);
        $this->assertEquals(
            'Un arrêté modificatif AM001 en date du 01/07/2024 a été pris concernant l\'arrêté AP45OL023 du 01/01/2024.',
            $this->suiviDescription
        );
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertEquals('AP45OL023', $additionalInformation['arrete_numero']);
        $this->assertEquals('01/07/2024', $additionalInformation['arrete_modificatif_date']);
        $this->assertEquals('AM001', $additionalInformation['arrete_modificatif_numero']);
    }

    public function testUpdateWithModificatifDateAndNumeroUpdated(): void
    {
        $arrete = $this->createArrete([
            'arrete_numero' => 'AP45OL023',
            'arrete_modificatif_date' => '01/07/2024',
            'arrete_modificatif_numero' => 'AM001',
        ]);

        // modification de la date et du numéro de l'arrêté modificatif
        $result = $this->update($arrete, modificatifDate: '02/07/2024', modificatifNumero: 'AM002');

        $this->assertTrue($result);
        $this->assertStringContainsString(
            'La date de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2024.',
            (string) $this->suiviDescription,
        );
        $this->assertStringContainsString(
            'Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM002.',
            (string) $this->suiviDescription,
        );
        $additionalInformation = $arrete->getAdditionalInformation();
        $this->assertEquals('AP45OL023', $additionalInformation['arrete_numero']);
        $this->assertEquals('02/07/2024', $additionalInformation['arrete_modificatif_date']);
        $this->assertEquals('AM002', $additionalInformation['arrete_modificatif_numero']);
    }

    public function testUpdateThrowsExceptionWhenDateIsMalformed(): void
    {
        $arrete = $this->createArrete();

        $this->expectException(\InvalidArgumentException::class);
        $this->update($arrete, date: '2024-01-02');
    }

    /**
     * @param array<string, string|null> $additionalInformation
     */
    private function createArrete(array $additionalInformation = [], ?string $mainLeveeDate = null): Arrete
    {
        $arrete = (new Arrete())
            ->setDateArrete(new \DateTimeImmutable('2024-01-01'))
            ->setArreteType(ArreteType::ARRETE_L_511_19_INSALUBRITE)
            ->setSignalement($this->getSignalement())
            ->setAdditionalInformation(array_merge([
                'numero_dossier' => 'DOSS456',
                'arrete_numero' => 'AP45OL023',
                'arrete_mainlevee_numero' => null,
                'arrete_modificatif_date' => null,
                'arrete_modificatif_numero' => null,
            ], $additionalInformation));

        if ($mainLeveeDate) {
            $arrete->setDateMainLevee(\DateTimeImmutable::createFromFormat('d/m/Y', $mainLeveeDate) ?: null);
        }

        return $arrete;
    }

    private function update(
        Arrete $arrete,
        string $date = '01/01/2024',
        ?string $numero = 'AP45OL023',
        ?string $mainLeveeDate = null,
        ?string $mainLeveeNumero = null,
        ?string $modificatifDate = null,
        ?string $modificatifNumero = null,
    ): bool {
        $dossierArrete = new DossierArreteSISH([
            'keyDataList' => [null, 123],
            'columnDataList' => [
                'SISH',
                'REF123',
                'DOSS456',
                $date,
                $numero,
                self::ESABORA_TYPE_LABEL,
                $mainLeveeDate,
                $mainLeveeNumero,
                $modificatifDate,
                $modificatifNumero,
            ],
        ]);

        $affectation = $this->getAffectation(PartnerType::ARS);
        $affectation->setSignalement($arrete->getSignalement());

        return $this->arreteManager->updateFromDossierArreteSISH($arrete, $dossierArrete, $affectation);
    }
}
