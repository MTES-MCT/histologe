<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserFactoryTest extends KernelTestCase
{
    public function testCreateUserInstance(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $territory = new Territory();
        $partner = new Partner();

        $user = (new UserFactory())->createInstanceFrom(
            roleLabel: 'Utilisateur',
            territory: $territory,
            partner: $partner,
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        $user->setPassword($hasher->hashPassword($user, 'password'));

        $errors = $validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getIsGenerique(), false);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
    }
}
