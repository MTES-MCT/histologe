<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserFactoryTest extends KernelTestCase
{
    public function testCreateUserInstance(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        /** @var TokenGeneratorInterface $tokenGenerator */
        $tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);

        $territory = new Territory();
        $partner = new Partner();

        $user = (new UserFactory($tokenGenerator))->createInstanceFrom(
            roleLabel: 'Utilisateur',
            territory: $territory,
            partner: $partner,
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        $errors = $validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getIsGenerique(), false);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
    }

    public function testCreateUserAdminInstanceWithoutPartnerAndTerritory(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        /** @var TokenGeneratorInterface $tokenGenerator */
        $tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);

        $territory = new Territory();
        $partner = new Partner();

        $user = (new UserFactory($tokenGenerator))->createInstanceFrom(
            partner: null,
            territory: null,
            roleLabel: 'Super Admin',
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        $errors = $validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getIsGenerique(), false);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
        $this->assertEquals($user->getTerritory(), null);
        $this->assertEquals($user->getPartner(), null);
    }

    public function testCreateUserFromArray(): void
    {
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        /** @var TokenGeneratorInterface $tokenGenerator */
        $tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);

        /** @var PartnerManager $partnerManager */
        $partnerManager = static::getContainer()->get(PartnerManager::class);
        /** @var Partner $partner */
        $partner = $partnerManager->findOneBy(['nom' => 'Random partner 63']);
        $data = [
            'roles' => 'ROLE_USER_PARTNER',
            'email' => 'john.doe-1@example.com',
            'nom' => 'Doe',
            'prenom' => 'John',
            'isMailingActive' => true,
        ];

        $user = (new UserFactory($tokenGenerator))->createInstanceFromArray($partner, $data);

        $errors = $validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getIsGenerique(), false);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
        $this->assertEquals($user->getTerritory(), $partner->getTerritory());
        $this->assertEquals($user->getPartner(), $partner);
    }
}
