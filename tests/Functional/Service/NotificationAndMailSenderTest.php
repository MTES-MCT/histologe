<?php

namespace App\Tests\Functional\Service;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\NotificationType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Repository\NotificationRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\NotificationAndMailSender;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
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
    private NotificationAndMailSender $notificationAndMailSender;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->notificationMailerRegistry = self::getContainer()->get(NotificationMailerRegistry::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->notificationRepository = self::getContainer()->get(NotificationRepository::class);
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);
        $this->notificationFactory = self::getContainer()->get(NotificationFactory::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->userSignalementSubscriptionRepository = self::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->partnerRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            true,
        );
    }

    public function testSendNewSignalement(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-18']);
        $this->notificationAndMailSender->sendNewSignalement($signalement);

        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($mail, 'Un nouveau signalement vous attend');
        $this->assertEmailAddressContains($mail, 'to', 'ne-pas-repondre@signal-logement.beta.gouv.fr');
        $this->assertCount(2, $mail->getBcc());
        $this->assertEmailAddressContains($mail, 'bcc', 'admin-territoire-13-01@signal-logement.fr');

        $notificationsSummary = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::NOUVEAU_SIGNALEMENT, 'waitMailingSummary' => true]);
        $this->assertCount(0, $notificationsSummary);
        $notificationNoSummary = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::NOUVEAU_SIGNALEMENT, 'waitMailingSummary' => false]);
        $this->assertCount(6, $notificationNoSummary);
    }

    public function testSendNewAffectation(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-08']);
        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->first();

        $this->notificationAndMailSender->sendNewAffectation($affectation);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($mail, 'Un nouveau signalement vous attend');
        $this->assertEmailAddressContains($mail, 'to', 'ne-pas-repondre@signal-logement.beta.gouv.fr');
        $this->assertCount(2, $mail->getBcc());
        $this->assertEmailAddressContains($mail, 'bcc', 'partenaire-34-04@signal-logement.fr');
        $this->assertEmailAddressContains($mail, 'bcc', 'user-partenaire-34-02@signal-logement.fr');

        $notificationsSummary = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::NOUVELLE_AFFECTATION, 'waitMailingSummary' => true]);
        $this->assertCount(2, $notificationsSummary);
        $notificationNoSummary = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::NOUVELLE_AFFECTATION, 'waitMailingSummary' => false]);
        $this->assertCount(2, $notificationNoSummary);
    }

    public function testSendSignalementIsClosedToPartners(): void
    {
        /** @var User $admin */
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-08']);
        $signalement->setMotifCloture(MotifCloture::DEPART_OCCUPANT);
        $signalement->setClosedBy($admin);
        $suivi = new Suivi();
        $suivi->setSignalement($signalement);
        $suivi->setCreatedBy($admin);
        $suivi->setType(Suivi::TYPE_PARTNER);
        $suivi->setDescription('Le signalement a été cloturé pour tous les partenaires avec le motif...');
        $this->entityManager->persist($suivi);

        $this->notificationAndMailSender->sendSignalementIsClosedToPartners($suivi);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($mail, 'Clôture du signalement');
        $this->assertEmailAddressContains($mail, 'to', 'ne-pas-repondre@signal-logement.beta.gouv.fr');
        $this->assertCount(2, $mail->getBcc());
        $this->assertEmailAddressContains($mail, 'bcc', 'partenaire-34-04@signal-logement.fr');
        $this->assertEmailAddressContains($mail, 'bcc', 'admin-territoire-34-01@signal-logement.fr');

        $notificationsSummary = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::CLOTURE_SIGNALEMENT, 'waitMailingSummary' => true]);
        $this->assertCount(0, $notificationsSummary);
        $notificationNoSummary = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::CLOTURE_SIGNALEMENT, 'waitMailingSummary' => false]);
        $this->assertCount(3, $notificationNoSummary);
    }

    public function testSendNewSuiviToAdminsAndPartners(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-10',
        ]);
        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@signal-logement.fr',
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($respTerritoire)
        ->setSignalement($signalement)
        ->setDescription('test description')
        ->setType(Suivi::TYPE_PARTNER)
        ->setIsPublic(true);

        $this->entityManager->persist($suivi);
        $existingNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners($suivi, true);

        $this->assertEmailCount(1);
        $newNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $subscriptions = $this->userSignalementSubscriptionRepository->findBy(['signalement' => $signalement]);
        $this->assertCount(count($subscriptions) + \count($existingNotifications), $newNotifications);
    }

    public function testSendNDemandeAbandonProcedureToUsager(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-4',
        ]);

        /** @var User $occupant */
        $occupant = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $signalement->getMailOccupant(),
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($occupant)
        ->setSignalement($signalement)
        ->setDescription('test description')
        ->setType(Suivi::TYPE_PARTNER)
        ->setIsPublic(true);

        $this->entityManager->persist($suivi);

        $expectedAdress = [$signalement->getMailOccupant(), $signalement->getMailDeclarant()];

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->partnerRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            true,
        );

        $notificationAndMailSender->sendDemandeAbandonProcedureToUsager($suivi);

        $this->assertEmailCount(2);
        $i = 0;
        foreach ($expectedAdress as $adressMail) {
            $email = $this->getMailerMessage($i);
            $this->assertEmailAddressContains($email, 'To', $adressMail);
            ++$i;
        }
        // le mail envoyé au tiers contient le nom de l'occupant
        $this->assertEmailHtmlBodyContains($email, $occupant->getNomComplet());
    }

    public function testSendDemandeAbandonProcedureToAdminsAndPartners(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-4',
        ]);

        /** @var User $occupant */
        $occupant = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $signalement->getMailOccupant(),
        ]);
        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@signal-logement.fr',
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($occupant)
        ->setSignalement($signalement)
        ->setDescription('test description')
        ->setType(Suivi::TYPE_PARTNER)
        ->setIsPublic(true);

        $this->entityManager->persist($suivi);

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->partnerRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            true,
        );

        $notificationAndMailSender->sendDemandeAbandonProcedureToAdminsAndPartners($suivi);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage(0);
        $this->assertEmailAddressContains($email, 'To', 'ne-pas-repondre@signal-logement.beta.gouv.fr');
        $this->assertEmailAddressContains($email, 'Bcc', $respTerritoire->getEmail());
    }

    public function testSendNewSuiviToUsagersProfilTiers(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-4',
        ]);

        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@signal-logement.fr',
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($respTerritoire)
        ->setSignalement($signalement)
        ->setDescription('test description')
        ->setType(Suivi::TYPE_PARTNER)
        ->setIsPublic(true);

        $this->entityManager->persist($suivi);

        $expectedAdress = [$signalement->getMailOccupant(), $signalement->getMailDeclarant()];

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->partnerRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            true,
        );

        $notificationAndMailSender->sendNewSuiviToUsagers($suivi);

        $this->assertEmailCount(2);
        $i = 0;
        foreach ($expectedAdress as $adressMail) {
            $email = $this->getMailerMessage($i);
            $this->assertEmailAddressContains($email, 'To', $adressMail);
            ++$i;
        }
    }

    public function testSendNewSuiviToUsagersTiersDeclarantIsAgent(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-1', // signalement actif tiers pro
        ]);
        /** @var User $agentDeclarant */
        $agentDeclarant = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $signalement->getMailDeclarant(),
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($agentDeclarant)
        ->setSignalement($signalement)
        ->setDescription('test description')
        ->setType(Suivi::TYPE_PARTNER)
        ->setIsPublic(true);

        $this->entityManager->persist($suivi);

        $signalement->setMailOccupant('temp_for_test@signal-logement.fr');
        $expectedAdress = [$signalement->getMailOccupant()];

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->partnerRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            true,
        );

        $notificationAndMailSender->sendNewSuiviToUsagers($suivi);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();

        foreach ($expectedAdress as $adressMail) {
            $this->assertEmailAddressContains($email, 'To', $adressMail);
        }
    }
}
