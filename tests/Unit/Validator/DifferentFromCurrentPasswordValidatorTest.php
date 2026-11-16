<?php

namespace App\Tests\Unit\Validator;

use App\Entity\User;
use App\Validator\DifferentFromCurrentPassword;
use App\Validator\DifferentFromCurrentPasswordValidator;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<DifferentFromCurrentPasswordValidator>
 */
class DifferentFromCurrentPasswordValidatorTest extends ConstraintValidatorTestCase
{
    private UserPasswordHasherInterface&Stub $passwordHasher;

    protected function setUp(): void
    {
        $this->passwordHasher = $this->createStub(UserPasswordHasherInterface::class);

        parent::setUp();
    }

    protected function createValidator(): DifferentFromCurrentPasswordValidator
    {
        return new DifferentFromCurrentPasswordValidator(
            $this->passwordHasher
        );
    }

    public function testNullOrEmptyValue(): void
    {
        $constraint = new DifferentFromCurrentPassword();

        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();

        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    public function testNoCurrentPassword(): void
    {
        $constraint = new DifferentFromCurrentPassword();
        $user = new User();

        $this->setProperty($user, 'plainPassword');
        $this->validator->validate('NewPassword!123', $constraint);
        $this->assertNoViolation();
    }

    public function testDifferentPassword(): void
    {
        $constraint = new DifferentFromCurrentPassword();
        $user = new User();
        $user->setPassword('$2y$13$hashedPassword');

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'NewPassword!123')
            ->willReturn(false);

        $this->validator = new DifferentFromCurrentPasswordValidator($passwordHasher);
        $this->validator->initialize($this->context);
        $this->setProperty($user, 'plainPassword');
        $this->validator->validate('NewPassword!123', $constraint);
        $this->assertNoViolation();
    }

    public function testSamePassword(): void
    {
        $constraint = new DifferentFromCurrentPassword();
        $user = new User();
        $user->setPassword('$2y$13$hashedPassword');

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'OldPassword!123')
            ->willReturn(true);

        $this->validator = new DifferentFromCurrentPasswordValidator($passwordHasher);
        $this->validator->initialize($this->context);
        $this->setProperty($user, 'plainPassword');
        $this->validator->validate('OldPassword!123', $constraint);

        $this->buildViolation('Votre nouveau mot de passe doit être différent de votre mot de passe actuel.')
            ->assertRaised();
    }
}
