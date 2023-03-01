<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Commune;
use App\Entity\Territory;
use App\Factory\CommuneFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommuneFactoryTest extends KernelTestCase
{
    public function testCreateCommune(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);

        $territory = new Territory();

        $itemNomCommune = 'Saint-Mars-du-Désert';
        $itemCodePostal = '44850';
        $itemCodeCommune = '44179';

        $commune = (new CommuneFactory())->createInstanceFrom(
            territory: $territory,
            nom: $itemNomCommune,
            codePostal: $itemCodePostal,
            codeInsee: $itemCodeCommune
        );

        $errors = $validator->validate($commune);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(Commune::class, $commune);

        $this->assertEquals('44850', $commune->getCodePostal());
        $this->assertFalse($commune->getIsZonePermisLouer());
    }

    public function testCreateCommuneZonePermisDeLouer(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);

        $territory = new Territory();

        $itemNomCommune = 'Tonnerre';
        $itemCodePostal = '89700';
        $itemCodeCommune = '89418';

        $commune = (new CommuneFactory())->createInstanceFrom(
            territory: $territory,
            nom: $itemNomCommune,
            codePostal: $itemCodePostal,
            codeInsee: $itemCodeCommune,
            isZonePermisLouer: true
        );

        $errors = $validator->validate($commune);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(Commune::class, $commune);

        $this->assertEquals('89418', $commune->getCodeInsee());
        $this->assertTrue($commune->getIsZonePermisLouer());
    }
}
