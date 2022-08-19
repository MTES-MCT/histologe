<?php

namespace App\DataFixtures\Loader;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LoadTagSignalementData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
