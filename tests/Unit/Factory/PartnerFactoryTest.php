<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Factory\PartnerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PartnerFactoryTest extends KernelTestCase
{
    public function testCreatePartnerInstanceWhenIsNotCommune(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $territory = new Territory();
        $partner = (new PartnerFactory())->createInstanceFrom(
            territory: new Territory(),
            name: 'HTL',
            email: 'htl@example.com'
        );

        $errors = $validator->validate($partner);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(Partner::class, $partner);

        $this->assertEquals($partner->getIsArchive(), false);
        $this->assertEquals($partner->getIsCommune(), false);
        $this->assertEmpty($partner->getInsee());
    }

    public function testCreatePartnerInstanceWhenIsCommune(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $territory = new Territory();
        $partner = (new PartnerFactory())->createInstanceFrom(
            territory: new Territory(),
            name: 'HTL',
            email: 'htl@example.com',
            isCommune: true,
            insee: ['99000']
        );

        $errors = $validator->validate($partner);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(Partner::class, $partner);

        $this->assertEquals($partner->getIsArchive(), false);
        $this->assertEquals($partner->getIsCommune(), true);
        $this->assertContains('99000', $partner->getInsee());
    }
}
