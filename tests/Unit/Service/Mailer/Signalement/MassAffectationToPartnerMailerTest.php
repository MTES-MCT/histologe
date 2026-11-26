<?php

namespace App\Tests\Unit\Service\Mailer\Signalement;

use App\Entity\Territory;
use App\Service\Mailer\Mail\Signalement\MassAffectationToPartnerMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MassAffectationToPartnerMailerTest extends TestCase
{
    private MassAffectationToPartnerMailer $mailer;

    protected function setUp(): void
    {
        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([['host_url', 'https://example.com']]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params): string => '/bo/signalements/'.$params['uuid']
        );

        $this->mailer = new MassAffectationToPartnerMailer(
            $this->createStub(MailerInterface::class),
            $parameterBag,
            $this->createStub(LoggerInterface::class),
            $urlGenerator,
        );
    }

    public function testSupportsType(): void
    {
        $this->assertTrue($this->mailer->supports(NotificationMailerType::TYPE_MASS_AFFECTATION_TO_PARTNER));
        $this->assertFalse($this->mailer->supports(NotificationMailerType::TYPE_AFFECTATION_NEW));
    }

    public function testMailerParamsBuildLinks(): void
    {
        $params = $this->mailer->getMailerParamsFromNotification($this->createNotificationMail([
            ['reference' => '2025-1', 'uuid' => 'uuid-1'],
            ['reference' => '2025-2', 'uuid' => 'uuid-2'],
        ]));

        $this->assertSame([
            'signalements' => [
                ['reference' => '2025-1', 'link' => 'https://example.com/bo/signalements/uuid-1'],
                ['reference' => '2025-2', 'link' => 'https://example.com/bo/signalements/uuid-2'],
            ],
        ], $params);
    }

    public function testSubjectSingular(): void
    {
        $this->mailer->updateMailerSubjectFromNotification($this->createNotificationMail([
            ['reference' => '2025-1', 'uuid' => 'uuid-1'],
        ]));

        $this->assertSame('[13] Un nouveau signalement vous attend.', $this->getSubject());
    }

    public function testSubjectPlural(): void
    {
        $this->mailer->updateMailerSubjectFromNotification($this->createNotificationMail([
            ['reference' => '2025-1', 'uuid' => 'uuid-1'],
            ['reference' => '2025-2', 'uuid' => 'uuid-2'],
            ['reference' => '2025-3', 'uuid' => 'uuid-3'],
        ]));

        $this->assertSame('[13] 3 nouveaux signalements vous attendent.', $this->getSubject());
    }

    /**
     * @param array<int, array{reference: string, uuid: string}> $signalements
     */
    private function createNotificationMail(array $signalements): NotificationMail
    {
        return new NotificationMail(
            type: NotificationMailerType::TYPE_MASS_AFFECTATION_TO_PARTNER,
            to: ['partenaire@example.com'],
            territory: (new Territory())->setZip('13'),
            isRecipientVisible: false,
            params: ['signalements' => $signalements],
        );
    }

    private function getSubject(): ?string
    {
        return (new \ReflectionProperty($this->mailer, 'mailerSubject'))->getValue($this->mailer);
    }
}
