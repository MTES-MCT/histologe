<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Repository\SignalementRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Rnb\RnbService;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Service\Signalement\ZipcodeProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementAddressUpdaterTest extends KernelTestCase
{
    private SignalementAddressUpdater $signalementAddressUpdater;
    private ZipcodeProvider $zipcodeProvider;
    private PostalCodeHomeChecker $postalCodeHomeChecker;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->signalementRepository = $container->get(SignalementRepository::class);
        $this->zipcodeProvider = $container->get(ZipcodeProvider::class);
        $this->postalCodeHomeChecker = $container->get(PostalCodeHomeChecker::class);

        $addressService = $this->createMock(AddressService::class);
        $rnbService = $this->createMock(RnbService::class);
        $rialService = $this->createMock(RialService::class);

        $this->signalementAddressUpdater = new SignalementAddressUpdater(
            $addressService,
            $rnbService,
            $rialService,
            $this->zipcodeProvider,
            $this->postalCodeHomeChecker,
            '0'
        );
    }

    #[DataProvider('provideCanUpdateWithNewAddressData')]
    public function testCanUpdateWithNewAddress(
        string $signalementReference,
        string $newCodePostal,
        string $newInsee,
        bool $expectedResult,
        string $description,
    ): void {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $signalementReference]);
        $this->assertNotNull($signalement, "Le signalement {$signalementReference} devrait exister");

        $canUpdate = $this->signalementAddressUpdater->canUpdateWithNewAddress(
            $signalement,
            $newCodePostal,
            $newInsee
        );

        $this->assertSame($expectedResult, $canUpdate, $description);
    }

    public static function provideCanUpdateWithNewAddressData(): \Generator
    {
        yield 'Bouches-du-Rhône: peut mettre à jour avec un autre code postal/insee du 13' => [
            'signalementReference' => '2022-1',
            'newCodePostal' => '13004',
            'newInsee' => '13203',
            'expectedResult' => true,
            'description' => 'Un signalement sur le territoire Bouches-du-Rhône peut être mis à jour avec un autre code postal/insee du 13',
        ];

        yield 'Bouches-du-Rhône: ne peut pas mettre à jour avec le code postal/insee d\'un autre territoire' => [
            'signalementReference' => '2022-1',
            'newCodePostal' => '01170',
            'newInsee' => '01173',
            'expectedResult' => false,
            'description' => 'Un signalement sur le territoire Bouches-du-Rhône ne peut pas être mis à jour avec le code postal/insee d\'un autre territoire',
        ];

        yield 'Rhône: ne peut pas mettre à jour avec le code postal/insee de la Métropole de Lyon' => [
            'signalementReference' => '2023-2',
            'newCodePostal' => '69001',
            'newInsee' => '69381',
            'expectedResult' => false,
            'description' => 'Un signalement sur le territoire Rhône ne peut pas être mis à jour avec le code postal/insee de la Métropole de Lyon (pourtant dans le 69)',
        ];

        yield 'Finistère: ne peut pas mettre à jour avec le code postal/insee d\'une commune non autorisée' => [
            'signalementReference' => '2023-24',
            'newCodePostal' => '29100',
            'newInsee' => '29046', // Douarnenez, non autorisé
            'expectedResult' => false,
            'description' => 'Un signalement sur le territoire Finistère ne peut pas être mis à jour avec le code postal/insee d\'ailleurs qu\'à Brest ou Quimper',
        ];

        yield 'Finistère: peut mettre à jour de Quimper vers Brest' => [
            'signalementReference' => '2023-24',
            'newCodePostal' => '29200',
            'newInsee' => '29019', // Brest, autorisé
            'expectedResult' => true,
            'description' => 'Un signalement sur le territoire Finistère peut être mis à jour de Quimper vers Brest',
        ];

        yield 'Bouches-du-Rhône: peut mettre à jour avec code postal uniquement' => [
            'signalementReference' => '2022-1',
            'newCodePostal' => '13004',
            'newInsee' => '',
            'expectedResult' => true,
            'description' => 'Un signalement peut être mis à jour avec seulement le code postal si celui-ci est actif',
        ];
    }
}
