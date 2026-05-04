<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Epci;
use App\Repository\EpciRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EpciRepositoryTest extends KernelTestCase
{
    private EpciRepository $epciRepository;

    protected function setUp(): void
    {
        $this->epciRepository = static::getContainer()->get(EpciRepository::class);
    }

    #[DataProvider('provideFindOneByCommuneInseeAndPostalCodeData')]
    public function testFindOneByCommuneInseeAndPostalCode(
        string $codeInsee,
        string $postalCode,
        ?string $expectedCode,
        ?string $expectedNom,
    ): void {
        $epci = $this->epciRepository->findOneByCommuneInseeAndPostalCode($codeInsee, $postalCode);

        if (null === $expectedCode) {
            $this->assertNull($epci);
        } else {
            $this->assertInstanceOf(Epci::class, $epci);
            $this->assertEquals($expectedCode, $epci->getCode());
            $this->assertEquals($expectedNom, $epci->getNom());
        }
    }

    public static function provideFindOneByCommuneInseeAndPostalCodeData(): \Generator
    {
        yield 'valid commune returns epci' => [
            'codeInsee' => '30007',
            'postalCode' => '30100',
            'expectedCode' => '200066918',
            'expectedNom' => 'CA Alès Agglomération',
        ];

        yield 'non existent commune returns null' => [
            'codeInsee' => '99999',
            'postalCode' => '99999',
            'expectedCode' => null,
            'expectedNom' => null,
        ];

        yield 'empty code insee returns null' => [
            'codeInsee' => '',
            'postalCode' => '30100',
            'expectedCode' => null,
            'expectedNom' => null,
        ];

        yield 'empty postal code returns null' => [
            'codeInsee' => '30007',
            'postalCode' => '',
            'expectedCode' => null,
            'expectedNom' => null,
        ];

        yield 'both empty returns null' => [
            'codeInsee' => '',
            'postalCode' => '',
            'expectedCode' => null,
            'expectedNom' => null,
        ];
    }
}
