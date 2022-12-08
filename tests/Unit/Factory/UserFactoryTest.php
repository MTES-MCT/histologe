<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserFactoryTest extends KernelTestCase
{
    private TokenGeneratorInterface $tokenGenerator;
    private ParameterBagInterface $parameterBag;
    private ValidatorInterface $validator;
    private PartnerManager $partnerManager;

    protected function setUp(): void
    {
        self::bootKernel();
        /* @var TokenGeneratorInterface tokenGenerator */
        $this->tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);
        /* @var ParameterBagInterface parameterBag */
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        /* @var ValidatorInterface validator */
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        /* @var PartnerManager partnerManager */
        $this->partnerManager = static::getContainer()->get(PartnerManager::class);
    }

    public function testCreateUserInstance(): void
    {
        $territory = new Territory();
        $partner = new Partner();

        $user = (new UserFactory($this->tokenGenerator, $this->parameterBag))->createInstanceFrom(
            roleLabel: 'Utilisateur',
            territory: $territory,
            partner: $partner,
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        $errors = $this->validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
    }

    public function testCreateUserAdminInstanceWithoutPartnerAndTerritory(): void
    {
        $territory = new Territory();
        $partner = new Partner();

        $user = (new UserFactory($this->tokenGenerator, $this->parameterBag))->createInstanceFrom(
            partner: null,
            territory: null,
            roleLabel: 'Super Admin',
            firstname: 'John',
            lastname: 'Doe',
            email: 'john.doe@example.com'
        );

        $errors = $this->validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
        $this->assertEquals($user->getTerritory(), null);
        $this->assertEquals($user->getPartner(), null);
    }

    public function testCreateUserFromArray(): void
    {
        /** @var Partner $partner */
        $partner = $this->partnerManager->findOneBy(['nom' => 'Partenaire 63-01']);
        $data = [
            'roles' => 'ROLE_USER_PARTNER',
            'email' => 'john.doe-1@example.com',
            'nom' => 'Doe',
            'prenom' => 'John',
            'isMailingActive' => true,
        ];

        $user = (new UserFactory($this->tokenGenerator, $this->parameterBag))->createInstanceFromArray($partner, $data);

        $errors = $this->validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), true);
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
        $this->assertEquals($user->getTerritory(), $partner->getTerritory());
        $this->assertEquals($user->getPartner(), $partner);
    }
}
