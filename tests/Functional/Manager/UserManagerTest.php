<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementUsager;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\SignalementUsagerManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserManagerTest extends KernelTestCase
{
    private NotificationMailerRegistry $notificationMailerRegistry;
    private EntityManagerInterface $entityManager;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private TokenGeneratorInterface $tokenGenerator;
    private ParameterBagInterface $parameterBag;
    private SignalementUsagerManager $signalementUsagerManager;
    private UserFactory $userFactory;
    private UserManager $userManager;

    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->notificationMailerRegistry = static::getContainer()->get(NotificationMailerRegistry::class);
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->passwordHasherFactory = static::getContainer()->get(PasswordHasherFactoryInterface::class);
        $this->tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->signalementUsagerManager = new SignalementUsagerManager($this->managerRegistry, SignalementUsager::class);
        $this->userFactory = static::getContainer()->get(UserFactory::class);
        $this->userManager = new UserManager(
            $this->notificationMailerRegistry,
            $this->passwordHasherFactory,
            $this->tokenGenerator,
            $this->parameterBag,
            $this->managerRegistry,
            $this->signalementUsagerManager,
            $this->userFactory,
            User::class,
        );
    }

    public function testCreateUserFromData()
    {
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['nom' => 'Partenaire 01-02']);

        $user = $this->userManager->createUserFromData(
            $partner,
            [
                'roles' => 'ROLE_USER_PARTNER',
                'prenom' => 'John',
                'nom' => 'Doe',
                'email' => 'john.doe@example.com',
                'isMailingActive' => false,
                'rights' => [],
            ]
        );

        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($user->getIsMailingActive(), false);
        $this->assertEquals($user->getPrenom(), 'John');
        $this->assertEquals($user->getStatut(), User::STATUS_INACTIVE);
        $this->assertNull($user->getRights());
        $this->assertEquals($user->getRightLabels(), '');
        $this->assertEmailCount(1);
    }

    public function testUpdateUserFromDataNameChanged()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);

        $user = $this->userManager->updateUserFromData(
            $user,
            [
                'roles' => 'ROLE_USER_PARTNER',
                'prenom' => $user->getPrenom(),
                'nom' => 'Pantani',
                'email' => $user->getEmail(),
                'isMailingActive' => $user->getIsMailingActive(),
                'rights' => ['Affectation'],
            ]
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user->getNom(), 'Pantani');
        $this->assertEquals($user->getRights(), ['Affectation']);
        $this->assertEquals($user->getRightLabels(), 'Affectation');
        $this->assertEmailCount(0);
    }

    public function testUpdateUserFromDataEmailChanged()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);

        $user = $this->userManager->updateUserFromData(
            $user,
            [
                'roles' => 'ROLE_USER_PARTNER',
                'prenom' => $user->getPrenom(),
                'nom' => 'Lennon',
                'email' => 'john.lennon@example.com',
                'isMailingActive' => true,
            ]
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user->getNom(), 'Lennon');
        $this->assertEquals($user->getEmail(), 'john.lennon@example.com');
        $this->assertEmailCount(1);
    }

    public function testTransferActiveUserToAnotherPartner()
    {
        /** @var User $userNewPartner */
        $userNewPartner = $this->getTransferedUserToPartner('user-01-01@histologe.fr', 'Partenaire 01-02');

        $this->assertEquals('Partenaire 01-02', $userNewPartner->getPartner()->getNom());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, ' Cliquez ci-dessous pour vous connecter à votre compte');
    }

    public function testTransferInactiveUserToAnotherPartner()
    {
        /** @var User $userNewPartner */
        $userNewPartner = $this->getTransferedUserToPartner('user-13-03@histologe.fr', 'Partenaire 13-03');

        $this->assertEquals('Partenaire 13-03', $userNewPartner->getPartner()->getNom());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Cliquez ci-dessous pour activer votre compte et définir votre mot de passe');
    }

    private function getTransferedUserToPartner(string $userEmail, string $partnerName): User
    {
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['nom' => $partnerName]);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $userEmail]);

        $this->userManager->transferUserToPartner($user, $partner);

        return $userRepository->findOneBy(['email' => $userEmail]);
    }

    public function testGetUserAndTypeForSignalementAndEmail()
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-120']);

        $user = $this->userManager->getOrCreateUserForSignalementAndEmail($signalement, $signalement->getMailOccupant());
        $this->assertEquals($signalement->getMailOccupant(), $user->getEmail());
        $type = $this->userManager->getUserTypeForSignalementAndUser($signalement, $user);
        $this->assertEquals(UserManager::OCCUPANT, $type);

        $user = $this->userManager->getOrCreateUserForSignalementAndEmail($signalement, $signalement->getMailDeclarant());
        $this->assertEquals($signalement->getMailDeclarant(), $user->getEmail());
        $type = $this->userManager->getUserTypeForSignalementAndUser($signalement, $user);
        $this->assertEquals(UserManager::DECLARANT, $type);

        $user = $this->userManager->getOrCreateUserForSignalementAndEmail($signalement, 'lalala@nanani.fr');
        $this->assertNull($user);
        $type = $this->userManager->getUserTypeForSignalementAndUser($signalement, $user);
        $this->assertNull($type);

        $signalement->setMailDeclarant($signalement->getMailOccupant());
        $user = $this->userManager->getOrCreateUserForSignalementAndEmail($signalement, $signalement->getMailDeclarant());
        $type = $this->userManager->getUserTypeForSignalementAndUser($signalement, $user);
        $this->assertEquals(UserManager::OCCUPANT, $type);
    }
}
