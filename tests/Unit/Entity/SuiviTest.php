<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\UserManager;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class SuiviTest extends KernelTestCase
{
    public function testCreateSuiviUsager(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $validator = self::getContainer()->get('validator');

        $faker = Factory::create();

        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->find(1);
        $userManager = self::getContainer()->get(UserManager::class);

        /** @var User $userOccupant */
        $userOccupant = $userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);

        $suivi = (new Suivi())
        ->setCreatedBy($userOccupant)
        ->setSignalement($signalement)
        ->setDescription($faker->text())
        ->setType(Suivi::TYPE_USAGER)
        ->setIsPublic(true);

        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($suivi);
        $this->assertEquals(0, $errors->count());
    }
}
