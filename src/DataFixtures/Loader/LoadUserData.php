<?php

namespace App\DataFixtures\Loader;

use App\Entity\User;
use App\EventSubscriber\UserAddedSubscriber;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

class LoadUserData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
        private PartnerRepository $partnerRepository,
        private UserPasswordHasherInterface $hasher,
        private EntityManagerInterface $entityManager,
        private UserAddedSubscriber $userAddedSubscriber
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $userRows = Yaml::parseFile(__DIR__.'/../Files/User.yml');
        foreach ($userRows['users'] as $row) {
            $this->loadUsers($manager, $row);
        }
        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager, array $row): void
    {
        // do not send activation mail on loading fixtures
        $this->entityManager->getEventManager()->removeEventSubscriber($this->userAddedSubscriber);

        $faker = Factory::create();
        $user = (new User())
            ->setRoles(json_decode($row['roles'], true))
            ->setStatut($row['statut'])
            ->setIsGenerique($row['is_generique'])
            ->setIsMailingActive($row['is_mailing_active'])
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setPartner($this->partnerRepository->findOneBy(['nom' => $row['partner']]))
            ->setEmail($row['email'])
            ->setPrenom($faker->firstName())
            ->setNom($faker->lastName());

        $password = $this->hasher->hashPassword($user, 'histologe');
        $user->setPassword($password);

        $manager->persist($user);
    }

    public function getOrder(): int
    {
        return 8;
    }
}
