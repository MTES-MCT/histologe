<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Partner;
use App\Entity\SignalementUsager;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\SignalementUsagerManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationService;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class UserManagerTest extends KernelTestCase
{
    private LoginLinkHandlerInterface $loginLinkHandler;
    private NotificationService $notificationService;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private TokenGeneratorInterface $tokenGenerator;
    private ParameterBagInterface $parameterBag;
    private SignalementUsagerManager $signalementUsagerManager;
    private UserFactory $userFactory;

    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->loginLinkHandler = $this->createMock(LoginLinkHandlerInterface::class);
        $this->notificationService = static::getContainer()->get(NotificationService::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->passwordHasherFactory = static::getContainer()->get(PasswordHasherFactoryInterface::class);
        $this->tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->signalementUsagerManager = new SignalementUsagerManager($this->managerRegistry, SignalementUsager::class);
        $this->userFactory = static::getContainer()->get(UserFactory::class);
    }

    public function testTransferActiveUserToAnotherPartner()
    {
        $userManager = new UserManager(
            $this->loginLinkHandler,
            $this->notificationService,
            $this->urlGenerator,
            $this->passwordHasherFactory,
            $this->tokenGenerator,
            $this->parameterBag,
            $this->managerRegistry,
            $this->signalementUsagerManager,
            $this->userFactory,
            User::class,
        );

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['nom' => 'Partenaire 01-02']);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);

        $userManager->transferUserToPartner($user, $partner);

        /** @var User $userNewPartner */
        $userNewPartner = $userRepository->findOneBy(['email' => 'user-01-01@histologe.fr']);

        $this->assertEquals('Partenaire 01-02', $userNewPartner->getPartner()->getNom());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, ' Cliquez ci-dessous pour vous connecter à votre compte');
    }

    public function testTransferInactiveUserToAnotherPartner()
    {
        $userManager = new UserManager(
            $this->loginLinkHandler,
            $this->notificationService,
            $this->urlGenerator,
            $this->passwordHasherFactory,
            $this->tokenGenerator,
            $this->parameterBag,
            $this->managerRegistry,
            $this->signalementUsagerManager,
            $this->userFactory,
            User::class,
        );

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-03@histologe.fr']);

        $userManager->transferUserToPartner($user, $partner);

        /** @var User $userNewPartner */
        $userNewPartner = $userRepository->findOneBy(['email' => 'user-13-03@histologe.fr']);

        $this->assertEquals('Partenaire 13-03', $userNewPartner->getPartner()->getNom());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Cliquez ci-dessous pour activer votre compte et définir votre mot de passe');
    }
}
