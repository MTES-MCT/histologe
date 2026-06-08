<?php

namespace App\Tests\Unit\Service\Mailer;

use App\Manager\FailedEmailManager;
use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
class AbstractNotificationMailerTest extends TestCase
{
    private MailerInterface&MockObject $mailerInterface;
    private ParameterBagInterface&MockObject $parameterBag;
    private FailedEmailManager&MockObject $failedEmailManager;
    private HubInterface $originalSentryHub;

    protected function setUp(): void
    {
        $this->originalSentryHub = SentrySdk::getCurrentHub();
        $this->mailerInterface = $this->createMock(MailerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->failedEmailManager = $this->createMock(FailedEmailManager::class);

        $this->parameterBag->method('get')->willReturnMap([
            ['mail_enable', true],
            ['host_url', 'https://example.com'],
            ['notifications_email', 'no-reply@example.com'],
            ['platform_name', 'TestPlatform'],
            ['reply_to_email', 'reply@example.com'],
        ]);
    }

    protected function tearDown(): void
    {
        SentrySdk::setCurrentHub($this->originalSentryHub);
        parent::tearDown();
    }

    public function testSendReturnsTrueOnSuccess(): void
    {
        $this->mailerInterface->expects($this->once())->method('send');
        $this->failedEmailManager->expects($this->never())->method('create');

        $result = $this->buildMailer()->send($this->buildNotificationMail());

        $this->assertTrue($result);
    }

    #[DataProvider('provideTemplateDisabledMessages')]
    public function testSendCapturesFatalSentryEventOnTemplateDisabledError(string $exceptionMessage): void
    {
        $mockHub = $this->buildSentryHubMock();
        $mockHub->expects($this->once())->method('captureException');
        SentrySdk::setCurrentHub($mockHub);

        $this->mailerInterface->expects($this->once())->method('send')->willThrowException(new \RuntimeException($exceptionMessage));
        $this->failedEmailManager->expects($this->once())->method('create');

        $result = $this->buildMailer()->send($this->buildNotificationMail());

        $this->assertFalse($result);
    }

    #[DataProvider('provideOtherErrorMessages')]
    public function testSendCapturesOnError(string $exceptionMessage): void
    {
        $mockHub = $this->buildSentryHubMock();
        $mockHub->expects($this->never())->method('captureException');
        SentrySdk::setCurrentHub($mockHub);

        $this->mailerInterface->expects($this->once())->method('send')->willThrowException(new \RuntimeException($exceptionMessage));
        $this->failedEmailManager->expects($this->once())->method('create');

        $result = $this->buildMailer()->send($this->buildNotificationMail());

        $this->assertFalse($result);
    }

    public function testSendDoesNotSaveFailedEmailForErrorSignalementType(): void
    {
        $this->mailerInterface->expects($this->once())->method('send')->willThrowException(new \RuntimeException('Some error'));
        $this->failedEmailManager->expects($this->never())->method('create');

        $result = $this->buildMailer(NotificationMailerType::TYPE_ERROR_SIGNALEMENT)
            ->send($this->buildNotificationMail(NotificationMailerType::TYPE_ERROR_SIGNALEMENT));

        $this->assertFalse($result);
    }

    public static function provideTemplateDisabledMessages(): \Generator
    {
        yield 'Brevo API format' => ['Unable to send an email: This template is disabled (code 400).'];
        yield 'Uppercase' => ['TEMPLATE IS DISABLED'];
        yield 'Lowercase' => ['template is disabled'];
        yield 'Words in different order' => ['Error: template 42 is disabled'];
    }

    public static function provideOtherErrorMessages(): \Generator
    {
        yield 'Invalid Request' => ['Unable to send an email: Invalid Request (code 500).'];
        yield 'email is not valid' => ['Unable to send an email: email is not valid in to (code 400).'];
        yield 'upstream connect error or disconnect/reset before headers' => ['Unable to send an email: upstream connect error or disconnect/reset before headers. reset reason: connection timeout (code 503).'];
        yield 'An email must have a "To", "Cc", or "Bcc" header' => ['An email must have a "To", "Cc", or "Bcc" header.'];
    }

    private function buildMailer(NotificationMailerType $mailerType = NotificationMailerType::TYPE_CRON): AbstractNotificationMailer
    {
        $logger = $this->createMock(LoggerInterface::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $mailer = new class($this->mailerInterface, $this->parameterBag, $logger, $urlGenerator, $mailerType) extends AbstractNotificationMailer {
            protected ?string $mailerSubject = 'Test subject';
            protected ?string $brevoTemplateId = '42';

            public function __construct(
                MailerInterface $mailer,
                ParameterBagInterface $parameterBag,
                LoggerInterface $logger,
                UrlGeneratorInterface $urlGenerator,
                NotificationMailerType $type,
            ) {
                parent::__construct($mailer, $parameterBag, $logger, $urlGenerator);
                $this->mailerType = $type;
            }

            public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
            {
                return [];
            }
        };

        $mailer->setFailedEmailManager($this->failedEmailManager);

        return $mailer;
    }

    private function buildNotificationMail(NotificationMailerType $type = NotificationMailerType::TYPE_CRON): NotificationMail
    {
        return new NotificationMail(type: $type, to: 'test@example.com');
    }

    private function buildSentryHubMock(): HubInterface&MockObject
    {
        $mockHub = $this->createMock(HubInterface::class);
        // withScope executes the callback so captureException inside it reaches the mock
        $mockHub->method('withScope')->willReturnCallback(static function (callable $callback): void {
            $callback(new Scope());
        });
        // configureScope is called in logAndSaveFailedEmail, execute silently
        $mockHub->method('configureScope')->willReturnCallback(static function (callable $callback): void {
            $callback(new Scope());
        });

        return $mockHub;
    }
}
