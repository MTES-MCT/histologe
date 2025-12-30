<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\UserPartner;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserFactoryTest extends KernelTestCase
{
    private ValidatorInterface $validator;
    private PartnerManager $partnerManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        $this->partnerManager = static::getContainer()->get(PartnerManager::class);
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

        $user = (new UserFactory())->createInstanceFromArray($data);
        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
        $user->addUserPartner($userPartner);

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertTrue($user->getIsMailingActive());
        $this->assertEquals(UserStatus::INACTIVE, $user->getStatut());
        $this->assertEquals($user->getFirstTerritory(), $partner->getTerritory());
        $this->assertEquals($user->getPartners()->first(), $partner);
    }
}
