<?php

namespace Tests\Functional\Service\Mailer;

use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\SummaryMailService;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SummaryMailServiceTest extends KernelTestCase
{
    private NotificationRepository $notificationRepository;
    private SummaryMailService $summaryMailService;
    private NotificationMailerRegistry $notificationMailerRegistry;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->notificationRepository = $this->getContainer()->get(NotificationRepository::class);
        $this->notificationMailerRegistry = $this->getContainer()->get(NotificationMailerRegistry::class);
        $this->userRepository = $this->getContainer()->get(UserRepository::class);

        $this->summaryMailService = new SummaryMailService(
            $this->notificationRepository,
            $this->notificationMailerRegistry,
            true
        );
    }

    public function testSendSummaryEmailIfNeeded(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-30@signal-logement.fr']);
        $res = $this->summaryMailService->sendSummaryEmailIfNeeded($user);

        $this->assertEquals(1, $res);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $mailHtml = $mail->getHtmlBody();
        $this->assertStringContainsString('Nouveaux signalements', $mailHtml);
        $this->assertStringContainsString('Nouveaux suivis', $mailHtml);
        $this->assertStringContainsString('Clôtures de signalements', $mailHtml);
        $this->assertStringContainsString('Clôtures de partenaires', $mailHtml);
        $this->assertStringContainsString('Clôture du partenaire <strong>Alès Agglomération</strong> sur le signalement', $mailHtml);
    }

    public function testSendSummaryEmailIfNeededWithAffectation(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $res = $this->summaryMailService->sendSummaryEmailIfNeeded($user);

        $this->assertEquals(1, $res);
        $this->assertEmailCount(1);
        /** @var NotificationEmail $mail */
        $mail = $this->getMailerMessages()[0];
        $mailHtml = $mail->getHtmlBody();
        $this->assertStringContainsString('Affectation sur le signalement', $mailHtml);
    }

    public function testSendSummaryEmailIfNeededWithSummaryDisabled(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-30@signal-logement.fr']);
        $user->setIsMailingSummary(false);
        $res = $this->summaryMailService->sendSummaryEmailIfNeeded($user);

        $this->assertEquals(0, $res);
        $this->assertEmailCount(0);
    }
}
