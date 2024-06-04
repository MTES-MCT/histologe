<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTest extends KernelTestCase
{
    use FixturesHelper;

    public function testCreateUserWithNomComplet(): void
    {
        $user = $this->getUser([User::ROLE_ADMIN_TERRITORY]);
        $this->assertEquals('John', $user->getPrenom());
        $this->assertEquals('Doe', $user->getNom());
        $this->assertEquals('DOE John', $user->getNomComplet());
    }

    /**
     * @dataProvider provideInvalidPassword
     */
    public function testPasswordValidationError(string $expectedResult, string $password)
    {
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);

        $user = new User();
        $user->setPassword($password);

        $errors = $validator->validate($user, null, ['password']);

        /** @var ConstraintViolationList $errors */
        $errorsAsString = (string) $errors;
        $this->assertStringContainsString($expectedResult, $errorsAsString);
    }

    public function testPasswordValidationSuccess()
    {
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);

        $user = new User();
        $user->setPassword('histologe-H1');

        $errors = $validator->validate($user, null, ['password']);

        $this->assertCount(0, $errors);
    }

    public function testUserAnonymization()
    {
        $user = $this->getUser([User::ROLE_ADMIN_TERRITORY]);
        $user->anonymize();
        $this->assertNull($user->getAnonymizedAt());

        $user->setStatut(User::STATUS_ARCHIVE);
        $user->anonymize();
        $this->assertNotNull($user->getAnonymizedAt());
        $this->assertEquals(User::ANONYMIZED_PRENOM, $user->getPrenom());
        $this->assertEquals(User::ANONYMIZED_NOM, $user->getNom());
        $this->assertStringStartsWith(User::ANONYMIZED_MAIL, $user->getEmail());

        $tmp = $user->getAnonymizedAt();
        $user->anonymize();
        $this->assertEquals($tmp, $user->getAnonymizedAt());
    }

    public function provideInvalidPassword(): \Generator
    {
        yield 'blank' => ['Cette valeur ne doit pas être vide', ''];
        yield 'short' => ['Le mot de passe doit contenir au moins 12 caratères', 'short'];
        yield 'no_uppercase' => ['Le mot de passe doit contenir au moins une lettre majuscule', 'nouppercase'];
        yield 'no_lowercase' => ['Le mot de passe doit contenir au moins une lettre minuscule', 'NOLOWERCASE'];
        yield 'no_digit' => ['Le mot de passe doit contenir au moins un chiffre', 'NoDigitNoDigit'];
        yield 'no_special' => ['Le mot de passe doit contenir au moins un caractère spécial', 'NoSpecial'];
    }
}
