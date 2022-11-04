<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Partner;
use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Service\NotificationService;
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

    }

    public function testSwitchActiveUserToAnotherPartner()
    {
        $userManager = new UserManager(
            $this->loginLinkHandler,
            $this->notificationService,
            $this->urlGenerator,
            $this->passwordHasherFactory,
            $this->tokenGenerator,
            $this->parameterBag,
            $this->managerRegistry,
            User::class);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['nom' => 'Gex']);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'partenaire-01-01@histologe.fr']);

        $userManager->switchUserToPartner($user, $partner);

        /** @var User $userNewPartner */
        $userNewPartner = $userRepository->findOneBy(['email' => 'partenaire-01-01@histologe.fr']);

        $this->assertEquals('Gex', $userNewPartner->getPartner()->getNom());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, ' Cliquez ci-dessous pour vous connecter à votre compte');
    }

    public function testSwitchInactiveUserToAnotherPartner()
    {
        $userManager = new UserManager(
            $this->loginLinkHandler,
            $this->notificationService,
            $this->urlGenerator,
            $this->passwordHasherFactory,
            $this->tokenGenerator,
            $this->parameterBag,
            $this->managerRegistry,
            User::class);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['nom' => 'Istres']);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'partenaire-13-03@histologe.fr']);

        $userManager->switchUserToPartner($user, $partner);

        /** @var User $userNewPartner */
        $userNewPartner = $userRepository->findOneBy(['email' => 'partenaire-13-03@histologe.fr']);

        $this->assertEquals('Istres', $userNewPartner->getPartner()->getNom());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Cliquez ci-dessous pour vous activer votre compte et définir votre mot de passe');
    }
}
