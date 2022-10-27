<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class PartnerTest extends KernelTestCase
{
    public function testPartnerWithUserIsValid(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $validator = self::getContainer()->get('validator');

        $territory = $entityManager->getRepository(Territory::class)->find(1);
        $faker = Factory::create();

        $user = (new User())
            ->setEmail($faker->email())
            ->setNom($faker->lastName())
            ->setPrenom($faker->firstName())
            ->setRoles([User::ROLES['Utilisateur']])
            ->setIsMailingActive(true)
            ->setIsGenerique(false)
            ->setPassword($faker->password());

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEmail($faker->companyEmail())
            ->setIsArchive(false)
            ->setIsCommune(false)
            ->setTerritory($territory)
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->randomKey());

        for ($i = 0; $i < 3; ++$i) {
            $user = (new User())
                ->setEmail($faker->email())
                ->setNom($faker->lastName())
                ->setPrenom($faker->firstName())
                ->setRoles([User::ROLES['Utilisateur']])
                ->setIsMailingActive(true)
                ->setIsGenerique(false)
                ->setPassword($faker->password(8));
            $partner->addUser($user);
        }

        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($partner);
        $this->assertEquals(0, $errors->count());
    }
}
