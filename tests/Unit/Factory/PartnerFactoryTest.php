<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Factory\PartnerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PartnerFactoryTest extends KernelTestCase
{
    private ParameterBagInterface $parameterBag;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        /* @var ParameterBagInterface parameterBag */
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        /* @var ValidatorInterface validator */
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testCreatePartnerInstanceWhenIsNotCommune(): void
    {
        $partner = (new PartnerFactory($this->parameterBag))->createInstanceFrom(
            territory: new Territory(),
            name: 'HTL',
            email: 'htl@example.com',
            type: PartnerType::ADIL,
        );

        $errors = $this->validator->validate($partner);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(Partner::class, $partner);

        $this->assertEquals($partner->getIsArchive(), false);
        $this->assertEquals($partner->getIsCommune(), false);
        $this->assertEmpty($partner->getInsee());
    }

    public function testCreatePartnerInstanceWhenIsCommune(): void
    {
        $partner = (new PartnerFactory($this->parameterBag))->createInstanceFrom(
            territory: new Territory(),
            name: 'HTL',
            email: 'htl@example.com',
            type: PartnerType::COMMUNE_SCHS,
            insee: '99000, 99001'
        );

        $errors = $this->validator->validate($partner);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(Partner::class, $partner);

        $this->assertEquals($partner->getIsArchive(), false);
        $this->assertEquals($partner->getIsCommune(), true);
        $this->assertContains('99000', $partner->getInsee());
    }
}
