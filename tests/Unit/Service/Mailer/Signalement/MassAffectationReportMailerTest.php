<?php

namespace App\Tests\Unit\Service\Mailer\Signalement;

use App\Service\Mailer\Mail\Signalement\MassAffectationReportMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MassAffectationReportMailerTest extends TestCase
{
    private MassAffectationReportMailer $mailer;

    protected function setUp(): void
    {
        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([['host_url', 'https://example.com']]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params): string => '/bo/signalements/'.$params['uuid']
        );

        $this->mailer = new MassAffectationReportMailer(
            $this->createStub(MailerInterface::class),
            $parameterBag,
            $this->createStub(LoggerInterface::class),
            $urlGenerator,
        );
    }

    public function testSupportsType(): void
    {
        $this->assertTrue($this->mailer->supports(NotificationMailerType::TYPE_MASS_AFFECTATION_REPORT));
        $this->assertFalse($this->mailer->supports(NotificationMailerType::TYPE_MASS_AFFECTATION_TO_PARTNER));
    }

    public function testMailerParamsBuildLinksForEachList(): void
    {
        $params = $this->mailer->getMailerParamsFromNotification($this->createNotificationMail([
            'partner' => 'Partenaire 13-03',
            'affected' => [['reference' => '2025-1', 'uuid' => 'uuid-1']],
            'alreadyAffected' => [['reference' => '2025-2', 'uuid' => 'uuid-2']],
            'outOfPerimeter' => [['reference' => '2025-3', 'uuid' => 'uuid-3']],
            'notActive' => [['reference' => '2025-4', 'uuid' => 'uuid-4']],
        ]));

        $this->assertSame([
            'partner' => 'Partenaire 13-03',
            'affected' => [['reference' => '2025-1', 'link' => 'https://example.com/bo/signalements/uuid-1']],
            'alreadyAffected' => [['reference' => '2025-2', 'link' => 'https://example.com/bo/signalements/uuid-2']],
            'outOfPerimeter' => [['reference' => '2025-3', 'link' => 'https://example.com/bo/signalements/uuid-3']],
            'notActive' => [['reference' => '2025-4', 'link' => 'https://example.com/bo/signalements/uuid-4']],
        ], $params);
    }

    public function testMailerParamsMissingListsAreEmpty(): void
    {
        $params = $this->mailer->getMailerParamsFromNotification($this->createNotificationMail([
            'partner' => 'Partenaire 13-03',
            'affected' => [['reference' => '2025-1', 'uuid' => 'uuid-1']],
        ]));

        $this->assertCount(1, $params['affected']);
        $this->assertSame([], $params['alreadyAffected']);
        $this->assertSame([], $params['outOfPerimeter']);
        $this->assertSame([], $params['notActive']);
    }

    public function testSubjectContainsPartnerName(): void
    {
        $this->mailer->updateMailerSubjectFromNotification($this->createNotificationMail([
            'partner' => 'Partenaire 13-03',
        ]));

        $subject = (new \ReflectionProperty($this->mailer, 'mailerSubject'))->getValue($this->mailer);
        $this->assertSame('Affectation en masse vers Partenaire 13-03 terminée', $subject);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function createNotificationMail(array $params): NotificationMail
    {
        return new NotificationMail(
            type: NotificationMailerType::TYPE_MASS_AFFECTATION_REPORT,
            to: 'admin@example.com',
            params: $params,
        );
    }
}
