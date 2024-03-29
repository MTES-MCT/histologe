<?php

namespace App\DataFixtures\Loader;

use App\Entity\User;
use App\EventListener\UserCreatedListener;
use App\Factory\UserFactory;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

class LoadUserData extends Fixture implements OrderedFixtureInterface
{
    private const PLAIN_HISTOLOGE = 'histologe';

    public function __construct(
        private TerritoryRepository $territoryRepository,
        private PartnerRepository $partnerRepository,
        private UserPasswordHasherInterface $hasher,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag,
        private UserFactory $userFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $userRows = Yaml::parseFile(__DIR__.'/../Files/User.yml');
        foreach ($userRows['users'] as $row) {
            $this->loadUsers($manager, $row);
        }
        $this->loadSystemUser($manager);
        $manager->flush();

        $connection = $this->entityManager->getConnection();
        $sql = 'UPDATE user SET created_at = DATE(created_at) - INTERVAL 15 DAY';
        $connection->prepare($sql)->executeQuery();
    }

    private function loadUsers(ObjectManager $manager, array $row): void
    {
        // do not send activation mail on loading fixtures
        $this->entityManager->getEventManager()->removeEventListener([Events::onFlush], UserCreatedListener::class);

        $faker = Factory::create();
        $user = (new User())
            ->setRoles(json_decode($row['roles'], true))
            ->setStatut($row['statut'])
            ->setIsMailingActive($row['is_mailing_active'])
            ->setEmail($row['email'])
            ->setPrenom($faker->firstName())
            ->setNom($faker->lastName());

        if (isset($row['territory'])) {
            $user->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));
        }

        if (isset($row['partner'])) {
            $user->setPartner($this->partnerRepository->findOneBy(['nom' => $row['partner']]));
        }

        if (isset($row['last_login_at'])) {
            $lastLoginAt = (new \DateTimeImmutable())->modify($row['last_login_at']);
            $user->setLastLoginAt($lastLoginAt);
        }

        if (isset($row['token'])) {
            $user
                ->setToken($row['token'])
                ->setTokenExpiredAt(
                    (new \DateTimeImmutable())->modify($this->parameterBag->get('token_lifetime'))
                );
        }

        $password = $this->hasher->hashPassword($user, self::PLAIN_HISTOLOGE);
        $user->setPassword($password);

        $manager->persist($user);
    }

    private function loadSystemUser(ObjectManager $manager)
    {
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Administrateurs Histologe ALL']);
        $user = $this->userFactory->createInstanceFrom(
            roleLabel: 'Super Admin',
            partner: $partner,
            territory: null,
            firstname: 'Histologe',
            lastname: 'Admin',
            email: $this->parameterBag->get('user_system_email'),
            isMailActive: false,
        );

        $password = $this->hasher->hashPassword($user, self::PLAIN_HISTOLOGE);
        $user->setStatut(User::STATUS_ACTIVE)->setPassword($password);

        $manager->persist($user);
    }

    public function getOrder(): int
    {
        return 7;
    }
}
