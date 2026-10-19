<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\UserStatus;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserFactoryTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testCreateUserInstance(): void
    {
        $user = (new UserFactory())->createInstanceFrom(
            roleLabel: 'Agent',
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertTrue($user->getIsMailingActive());
        $this->assertEquals(UserStatus::INACTIVE, $user->getStatut());
    }

    public function testCreateUserAdminInstanceWithoutPartnerAndTerritory(): void
    {
        $user = (new UserFactory())->createInstanceFrom(
            roleLabel: 'Super Admin',
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertTrue($user->getIsMailingActive());
        $this->assertEquals(UserStatus::INACTIVE, $user->getStatut());
        $this->assertNull($user->getFirstTerritory());
        $this->assertEquals(0, $user->getPartners()->count());
    }
}
