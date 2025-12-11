<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\User;
use App\Entity\UserPartner;
use App\EventListener\UserCreatedListener;
use App\Factory\UserFactory;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Service\Sanitizer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

class LoadUserData extends Fixture implements OrderedFixtureInterface
{
    private const string APP_PLAIN_PASSWORD = 'signallogement';

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UserFactory $userFactory,
        private readonly UserCreatedListener $userCreatedListener,
        private readonly UserManager $userManager,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        // do not send activation mail on loading fixtures
        $this->entityManager->getEventManager()->removeEventListener([Events::postPersist], $this->userCreatedListener);

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

    /**
     * @param array<string, mixed> $row
     *
     * @throws \DateMalformedStringException
     */
    private function loadUsers(ObjectManager $manager, array $row): void
    {
        $faker = Factory::create();
        $statut = UserStatus::from($row['statut']);
        $user = (new User())
            ->setRoles(json_decode($row['roles'], true))
            ->setStatut($statut)
            ->setIsMailingActive($row['is_mailing_active'] ?? false)
            ->setPrenom($faker->firstName())
            ->setNom($faker->lastName());

        $this->userManager->loadUserTokenForUser($user, false);

        if (isset($row['has_permission_affectation'])) {
            $user->setHasPermissionAffectation($row['has_permission_affectation']);
        }

        if (UserStatus::ARCHIVE->value === $row['statut']) {
            $user->setEmail(Sanitizer::tagArchivedEmail($row['email']));
        } else {
            $user->setEmail($row['email']);
        }

        if (isset($row['partners'])) {
            foreach ($row['partners'] as $partner) {
                $userPartner = new UserPartner();
                $userPartner->setUser($user)->setPartner($this->partnerRepository->findOneBy(['nom' => $partner]));
                $manager->persist($userPartner);
            }
        } elseif (isset($row['partner'])) {
            $userPartner = new UserPartner();
            $userPartner->setUser($user)->setPartner($this->partnerRepository->findOneBy(['nom' => $row['partner']]));
            $manager->persist($userPartner);
        }

        if (isset($row['last_login_at'])) {
            $lastLoginAt = (new \DateTimeImmutable())->modify($row['last_login_at']);
            $user->setLastLoginAt($lastLoginAt);
        }

        if (isset($row['anonymized']) && $row['anonymized']) {
            $user->anonymize();
        }

        if (isset($row['token'])) {
            $user
                ->setToken($row['token'])
                ->setTokenExpiredAt(
                    (new \DateTimeImmutable())->modify($this->parameterBag->get('token_lifetime'))
                );
        }

        if (isset($row['archiving_scheduled'])) {
            $user->setArchivingScheduledAt(
                (new \DateTimeImmutable())->modify('+15 days')
            );
        }

        if (isset($row['is_mailing_summary'])) {
            $user->setIsMailingSummary($row['is_mailing_summary']);
        }

        if (isset($row['has_checked_last_cgu'])) {
            $user->setCguVersionChecked($this->parameterBag->get('cgu_current_version'));
        }

        $password = $this->hasher->hashPassword($user, self::APP_PLAIN_PASSWORD);
        $user->setPassword($password);

        $manager->persist($user);
    }

    private function loadSystemUser(ObjectManager $manager): void
    {
        $user = $this->userFactory->createInstanceFrom(
            roleLabel: 'Super Admin',
            firstname: 'Signal-logement',
            lastname: 'Admin',
            email: $this->parameterBag->get('user_system_email'),
            isMailActive: false,
        );
        $password = $this->hasher->hashPassword($user, self::APP_PLAIN_PASSWORD);
        $user->setStatut(UserStatus::ACTIVE)->setPassword($password);
        $manager->persist($user);

        $partner = $this->partnerRepository->findOneBy(['nom' => Partner::DEFAULT_PARTNER]);
        $userPartner = new UserPartner();
        $userPartner->setUser($user)->setPartner($partner);
        $manager->persist($userPartner);
    }

    public function getOrder(): int
    {
        return 8;
    }
}
