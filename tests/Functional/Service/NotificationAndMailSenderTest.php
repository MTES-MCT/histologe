<?php

namespace App\Tests\Functional\Service;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Repository\NotificationRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\NotificationAndMailSender;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationAndMailSenderTest extends KernelTestCase
{
    use FixturesHelper;

    private EntityManagerInterface $entityManager;
    private NotificationMailerRegistry $notificationMailerRegistry;
    private UserRepository $userRepository;
    private NotificationRepository $notificationRepository;
    private PartnerRepository $partnerRepository;
    private NotificationFactory $notificationFactory;
    private Security $security;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->notificationRepository = self::getContainer()->get(NotificationRepository::class);
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);
        $this->notificationFactory = self::getContainer()->get(NotificationFactory::class);
        $this->security = static::getContainer()->get('security.helper');
    }

    public function testSendNewSuiviToAdminsAndPartners(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-10',
        ]);
        $territory = $signalement->getTerritory();
        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@histologe.fr',
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($respTerritoire)
        ->setSignalement($signalement)
        ->setDescription('test description')
        ->setType(Suivi::TYPE_PARTNER)
        ->setIsPublic(true);

        $this->entityManager->persist($suivi);

        $expectedAdress = [$respTerritoire->getEmail()];
        $expectedNotification = $this->userRepository->findActiveAdminsAndTerritoryAdmins($territory);
        foreach ($signalement->getAffectations() as $affectation) {
            if (AffectationStatus::STATUS_WAIT->value === $affectation->getStatut()
                    || AffectationStatus::STATUS_ACCEPTED->value === $affectation->getStatut()) {
                $partner = $affectation->getPartner();

                if ($partnerEmail = $partner->getEmail()) {
                    $expectedAdress[] = $partnerEmail;
                }

                foreach ($partner->getUsers() as $user) {
                    if (User::STATUS_ACTIVE === $user->getStatut()) {
                        if ($user->getIsMailingActive()) {
                            $expectedAdress[] = $user->getEmail();
                        }
                        $expectedNotification[] = $user;
                    }
                }
            }
        }

        // suivi creator doesn't receive notification
        unset($expectedNotification[array_search($suivi->getCreatedBy(), $expectedNotification)]);

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->partnerRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
        );

        $notificationAndMailSender->sendNewSuiviToAdminsAndPartners($suivi, true);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();

        foreach ($expectedAdress as $adressMail) {
            $this->assertEmailAddressContains($email, 'Bcc', $adressMail);
        }

        $newNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $expectedNotificationIds = array_map(fn ($user) => $user->getId(), $expectedNotification);
        $newNotificationIds = array_map(fn ($notification) => $notification->getUser()->getId(), $newNotifications);

        sort($expectedNotificationIds);
        sort($newNotificationIds);

        $this->assertEquals(\count($expectedNotification), \count($newNotifications));
        $this->assertEquals($expectedNotificationIds, $newNotificationIds);
    }
}
