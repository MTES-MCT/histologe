<?php

namespace App\Tests\Unit\Validator;

use App\Entity\User;
use App\Validator\DifferentFromCurrentPassword;
use App\Validator\DifferentFromCurrentPasswordValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<DifferentFromCurrentPasswordValidator>
 */
class DifferentFromCurrentPasswordValidatorTest extends ConstraintValidatorTestCase
{
    private UserPasswordHasherInterface&MockObject $passwordHasher;

    protected function setUp(): void
    {
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

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

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'NewPassword!123')
            ->willReturn(false);

        $this->setProperty($user, 'plainPassword');
        $this->validator->validate('NewPassword!123', $constraint);
        $this->assertNoViolation();
    }

    public function testSamePassword(): void
    {
        $constraint = new DifferentFromCurrentPassword();
        $user = new User();
        $user->setPassword('$2y$13$hashedPassword');

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'OldPassword!123')
            ->willReturn(true);

        $this->setProperty($user, 'plainPassword');
        $this->validator->validate('OldPassword!123', $constraint);

        $this->buildViolation('Votre nouveau mot de passe doit être différent de votre mot de passe actuel.')
            ->assertRaised();
    }
}
