<?php

namespace App\DataFixtures\Loader;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LoadTagData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
