<?php

namespace App\Tests\Functional\Service\Notification;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\InjonctionBailleur\CourrierBailleurGenerator;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Notification\NotificationAndMailSender;
use App\Service\Signalement\Suivi\SuiviMentionExtractor;
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
    private NotificationFactory $notificationFactory;
    private Security $security;
    private NotificationAndMailSender $notificationAndMailSender;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private SuiviMentionExtractor $suiviMentionExtractor;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->notificationMailerRegistry = static::getContainer()->get(NotificationMailerRegistry::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->notificationRepository = static::getContainer()->get(NotificationRepository::class);
        $this->notificationFactory = static::getContainer()->get(NotificationFactory::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->suiviMentionExtractor = static::getContainer()->get(SuiviMentionExtractor::class);
        /** @var CourrierBailleurGenerator $courrierBailleurGenerator */
        $courrierBailleurGenerator = static::getContainer()->get(CourrierBailleurGenerator::class);
        $this->notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            $courrierBailleurGenerator,
            $this->userSignalementSubscriptionRepository,
            $this->suiviMentionExtractor,
        );
    }

    public function testSendNewSignalement(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-18']);
        $this->notificationAndMailSender->sendNewSignalement($signalement);
        $this->entityManager->flush();

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

    public function testSendNewSignalementInjonction(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->notificationAndMailSender->sendNewSignalementInjonction($signalement);

        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($mail, 'Manquements aux réglementations concernant un de vos logements');
        $this->assertEmailAddressContains($mail, 'to', $signalement->getMailProprio());
        $this->assertEmailAttachmentCount($mail, 1);
        $this->assertEmailHasHeader($mail, 'templateId', '253');
    }

    public function testSendUsagerCloseInjonctionToBailleur(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->notificationAndMailSender->sendUsagerCloseInjonctionToBailleur($signalement);

        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($mail, 'Votre locataire a mis fin à la procédure concernant votre logement');
        $this->assertEmailAddressContains($mail, 'to', $signalement->getMailProprio());
        $this->assertEmailHasHeader($mail, 'templateId', '296');
    }

    public function testSendNewAffectation(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-08']);
        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->first();

        $this->notificationAndMailSender->sendNewAffectation($affectation);
        $this->entityManager->flush();
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
        $suivi->setDescription('Le signalement a été clôturé pour tous les partenaires avec le motif...');
        $this->entityManager->persist($suivi);

        $this->notificationAndMailSender->sendSignalementIsClosedToPartners($suivi);
        $this->entityManager->flush();
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
        ->setIsVisibleForUsager(true);

        $this->entityManager->persist($suivi);
        $existingNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners($suivi, true);
        $this->entityManager->flush();

        $this->assertEmailCount(1);
        $newNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $subscriptions = $this->userSignalementSubscriptionRepository->findBy(['signalement' => $signalement]);
        $this->assertCount(count($subscriptions) + \count($existingNotifications), $newNotifications);
    }

    public function testSendNewSuiviToAdminsAndPartnersWithMentionNotifiesOnlyMentionedPartnerSubscribers(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-10',
        ]);
        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@signal-logement.fr',
        ]);
        // abonné au partenaire 3 ("Partenaire 13-02"), affectation EN_COURS sur 2022-10, is_mailing_summary = 1
        /** @var User $mentionedPartnerUser */
        $mentionedPartnerUser = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'user-13-01@signal-logement.fr',
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($respTerritoire)
        ->setSignalement($signalement)
        ->setDescription('test description avec mention <span class="mention" data-partner-id="3">@Partenaire 13-02</span>')
        ->setType(Suivi::TYPE_PARTNER)
        ->setCategory(SuiviCategory::MESSAGE_PARTNER)
        ->setIsVisibleForUsager(false);

        $this->entityManager->persist($suivi);
        $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners($suivi, true);
        $this->entityManager->flush();

        // user-13-01 est en mode récap : pas de mail immédiat, seulement une notification in-app en attente de récap
        $this->assertEmailCount(0);

        $newNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $this->assertCount(1, $newNotifications);
        $this->assertSame(NotificationType::NOUVELLE_MENTION, $newNotifications[0]->getType());
        $this->assertSame($mentionedPartnerUser->getEmail(), $newNotifications[0]->getUser()->getEmail());
        $this->assertTrue($newNotifications[0]->isWaitMailingSummary());
        $this->assertStringContainsString($respTerritoire->getNomComplet(), (string) $newNotifications[0]->getDescription());
        $this->assertStringContainsString($signalement->getReference(), (string) $newNotifications[0]->getDescription());
    }

    public function testSendNewSuiviToAdminsAndPartnersWithMentionSendsImmediateEmailForNonSummaryUser(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-10',
        ]);
        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@signal-logement.fr',
        ]);
        // abonné au partenaire 4 ("Partenaire 13-03"), affectation EN_COURS sur 2022-10, is_mailing_summary = 0
        /** @var User $mentionedPartnerUser */
        $mentionedPartnerUser = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'user-13-02@signal-logement.fr',
        ]);

        $suivi = (new Suivi())
        ->setCreatedBy($respTerritoire)
        ->setSignalement($signalement)
        ->setDescription('test description avec mention <span class="mention" data-partner-id="4">@Partenaire 13-03</span>')
        ->setType(Suivi::TYPE_PARTNER)
        ->setCategory(SuiviCategory::MESSAGE_PARTNER)
        ->setIsVisibleForUsager(false);

        $this->entityManager->persist($suivi);
        $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners($suivi, true);
        $this->entityManager->flush();

        $this->assertEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(), 'bcc', $mentionedPartnerUser->getEmail());

        $newNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $this->assertCount(1, $newNotifications);
        $this->assertSame(NotificationType::NOUVELLE_MENTION, $newNotifications[0]->getType());
        $this->assertFalse($newNotifications[0]->isWaitMailingSummary());
    }

    public function testSendNewSuiviToAdminsAndPartnersWithMentionOnPartnerWithoutAcceptedAffectationFallsBackToStandardBroadcast(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => '2022-1',
        ]);
        /** @var User $respTerritoire */
        $respTerritoire = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'admin-territoire-13-01@signal-logement.fr',
        ]);

        // partenaire 2 ("Partenaire 13-01") a une affectation NOUVEAU (pas encore acceptée) sur 2022-1 :
        // la mention est ignorée et on retombe sur la diffusion standard "nouveau suivi"
        $suivi = (new Suivi())
        ->setCreatedBy($respTerritoire)
        ->setSignalement($signalement)
        ->setDescription('test description avec mention sur affectation non acceptée <span class="mention" data-partner-id="2">@Partenaire 13-01</span>')
        ->setType(Suivi::TYPE_PARTNER)
        ->setCategory(SuiviCategory::MESSAGE_PARTNER)
        ->setIsVisibleForUsager(false);

        $this->entityManager->persist($suivi);
        $this->notificationAndMailSender->sendNewSuiviToAdminsAndPartners($suivi, true);
        $this->entityManager->flush();

        $this->assertEmailCount(1);
        $newNotifications = $this->notificationRepository->findBy(['suivi' => $suivi]);
        $this->assertNotEmpty($newNotifications);
        foreach ($newNotifications as $notification) {
            $this->assertSame(NotificationType::NOUVEAU_SUIVI, $notification->getType());
        }
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
        ->setIsVisibleForUsager(true);

        $this->entityManager->persist($suivi);

        $expectedAdress = [$signalement->getMailOccupant(), $signalement->getMailDeclarant()];

        /** @var CourrierBailleurGenerator $courrierBailleurGenerator */
        $courrierBailleurGenerator = static::getContainer()->get(CourrierBailleurGenerator::class);

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            $courrierBailleurGenerator,
            $this->userSignalementSubscriptionRepository,
            $this->suiviMentionExtractor,
        );

        $notificationAndMailSender->sendDemandeAbandonProcedureToUsager($suivi);
        $this->entityManager->flush();

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
        ->setIsVisibleForUsager(true);

        $this->entityManager->persist($suivi);

        /** @var CourrierBailleurGenerator $courrierBailleurGenerator */
        $courrierBailleurGenerator = static::getContainer()->get(CourrierBailleurGenerator::class);

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            $courrierBailleurGenerator,
            $this->userSignalementSubscriptionRepository,
            $this->suiviMentionExtractor,
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
        ->setIsVisibleForUsager(true);

        $this->entityManager->persist($suivi);

        $expectedAdress = [$signalement->getMailOccupant(), $signalement->getMailDeclarant()];

        /** @var CourrierBailleurGenerator $courrierBailleurGenerator */
        $courrierBailleurGenerator = static::getContainer()->get(CourrierBailleurGenerator::class);

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            $courrierBailleurGenerator,
            $this->userSignalementSubscriptionRepository,
            $this->suiviMentionExtractor,
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
        ->setIsVisibleForUsager(true);

        $this->entityManager->persist($suivi);

        $signalement->setMailOccupant('temp_for_test@signal-logement.fr');
        $expectedAdress = [$signalement->getMailOccupant()];

        /** @var CourrierBailleurGenerator $courrierBailleurGenerator */
        $courrierBailleurGenerator = static::getContainer()->get(CourrierBailleurGenerator::class);

        $notificationAndMailSender = new NotificationAndMailSender(
            $this->entityManager,
            $this->userRepository,
            $this->notificationFactory,
            $this->notificationMailerRegistry,
            $this->security,
            $courrierBailleurGenerator,
            $this->userSignalementSubscriptionRepository,
            $this->suiviMentionExtractor,
        );

        $notificationAndMailSender->sendNewSuiviToUsagers($suivi);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();

        foreach ($expectedAdress as $adressMail) {
            $this->assertEmailAddressContains($email, 'To', $adressMail);
        }
    }
}
