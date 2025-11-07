<?php

namespace App\Service\Mailer\Mail;

use App\Entity\Suivi;
use App\Entity\Territory;
use App\Manager\FailedEmailManager;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Sentry\State\Scope;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractNotificationMailer implements NotificationMailerInterface
{
    protected ?NotificationMailerType $mailerType = null;
    protected ?string $mailerSubject = null;
    protected ?string $mailerButtonText = null;
    protected ?string $mailerTemplate = null;
    protected ?string $brevoTemplateId = null;
    protected ?string $tagHeader = null;
    /**
     * @var array<mixed>
     */
    protected array $mailerParams = [];
    private ?FailedEmailManager $failedEmailManager = null;

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function send(NotificationMail $notificationMail): bool
    {
        if (!$this->parameterBag->get('mail_enable')) {
            $this->logger->info('E-mail has been disable, please enable MAIL_ENABLE=1');

            return true;
        }
        $territory = $notificationMail->getTerritory();
        $this->mailerParams = $this->getMailerParamsFromNotification($notificationMail);
        $this->updateMailerSubjectFromNotification($notificationMail);
        $this->updateButtonTextFromNotification($notificationMail);
        $params = [
            'template' => $this->mailerTemplate,
            'template_id' => $this->brevoTemplateId,
            'subject' => $this->mailerSubject,
            'btntext' => $this->mailerButtonText,
            'url' => $this->parameterBag->get('host_url'),
            'tagHeader' => $this->tagHeader,
        ];

        $params = array_merge($params, $notificationMail->getParams(), $this->mailerParams);
        $message = $this->renderMailContentWithParams($params, $territory ?? null);

        if (null !== $this->tagHeader) {
            $message->getHeaders()->add(new TagHeader($this->tagHeader));
        }

        if (!empty($this->brevoTemplateId)) {
            $brevoParams = $params;
            unset($brevoParams['attachContent']);
            $message->getHeaders()
                ->addTextHeader('templateId', $this->brevoTemplateId)
                ->addParameterizedHeader('params', 'params', $brevoParams);
        }

        $territoryName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')
            ->transliterate(
                (!empty($territory) && null !== $territory->getName())
                    ? $territory->getName()
                    : 'ALERTE'
            );

        if (!$notificationMail->isRecipientVisible() && isset($message->getReplyTo()[0])) {
            $message->addTo($message->getReplyTo()[0]);
        }

        foreach ($notificationMail->getEmails() as $email) {
            try {
                if ($notificationMail->isRecipientVisible()) {
                    $email && $message->addTo($email);
                } else {
                    $email && $message->addBcc($email);
                }
            } catch (\Exception $e) {
                $this->logger->error(\sprintf('[%s] %s', $notificationMail->getType()->name, $e->getMessage()));
            }
        }

        $message->from(
            new Address(
                $this->parameterBag->get('notifications_email'),
                mb_strtoupper($this->getPlatformName().' - '.$territoryName)
            )
        );

        if (!empty($params['attach'])) {
            if (\is_array($params['attach'])) {
                foreach ($params['attach'] as $attachPath) {
                    $message->attachFromPath($attachPath);
                }
            } else {
                $message->attachFromPath($params['attach']);
            }
        }
        if (!empty($params['attachContent'])) {
            $message->attach(
                $params['attachContent']['content'],
                $params['attachContent']['filename'],
                'application/pdf'
            );
        }
        try {
            $this->mailer->send($message);

            return true;
        } catch (\Throwable $exception) {
            $this->logAndSaveFailedEmail($message, $notificationMail, $exception, $params);
        }

        return false;
    }

    /**
     * @param array<mixed> $params
     */
    private function logAndSaveFailedEmail(
        NotificationEmail $message,
        NotificationMail $notificationMail,
        \Throwable $exception,
        array $params,
    ): void {
        $object = $params['entity'] ?? null;
        $notifyUsager = false;
        if ($object instanceof Suivi) {
            $notifyUsager = $object->getIsPublic();
        } elseif (str_contains($this->mailerType->name, 'USAGER')) {
            $notifyUsager = true;
        }
        \Sentry\configureScope(function (Scope $scope) use ($notifyUsager): void {
            $scope->setTag('mailer_type', $this->mailerType->name);
            $scope->setTag('notify_usager', $notifyUsager ? 'yes' : 'no');
        });
        $this->logger->error(\sprintf(
            '[%s] %s',
            $notificationMail->getType()->name, $exception->getMessage()
        ));
        if (NotificationMailerType::TYPE_ERROR_SIGNALEMENT !== $notificationMail->getType()) {
            try {
                $this->getFailedEmailManager()->create(
                    $message, $notificationMail, $exception, $this->parameterBag->get('reply_to_email'), $notifyUsager);
            } catch (\Exception $e) {
                $this->logger->error('Failed to save FailedEmail: '.$e->getMessage());
            }
        }
    }

    public function supports(NotificationMailerType $type): bool
    {
        return $this->mailerType === $type;
    }

    public function generateLinkSignalementView(string $uuid): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $uuid]
        );
    }

    /**
     * @param array<mixed> $params
     */
    public function generateLink(string $route, array $params): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate($route, $params);
    }

    /**
     * @param array<mixed> $params
     */
    public function generateAbsoluteLink(string $route, array $params): string
    {
        return $this->urlGenerator->generate(
            $route,
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
    }

    public function updateButtonTextFromNotification(NotificationMail $notificationMail): void
    {
    }

    /**
     * @return array<mixed>
     */
    abstract public function getMailerParamsFromNotification(NotificationMail $notificationMail): array;

    /**
     * @param array<mixed> $params
     */
    private function renderMailContentWithParams(
        array $params,
        ?Territory $territory,
    ): NotificationEmail {
        $notification = new NotificationEmail();
        $notification->markAsPublic();
        if (null === $this->brevoTemplateId) {
            $notification->htmlTemplate('emails/'.$this->mailerTemplate.'.html.twig');
        }

        return $notification->context($params)
            ->replyTo($this->parameterBag->get('reply_to_email'))
            ->subject(
                mb_strtoupper($this->getPlatformName())
                .' '
                .mb_strtoupper((!empty($territory) && null !== $territory->getName()) ? $territory->getName() : 'ALERTE')
                .' - '.
                str_replace('%param.platform_name%', $this->getPlatformName(), $this->mailerSubject)
            );
    }

    public function setFailedEmailManager(FailedEmailManager $failedEmailManager): void
    {
        $this->failedEmailManager = $failedEmailManager;
    }

    public function getFailedEmailManager(): FailedEmailManager
    {
        if (!$this->failedEmailManager) {
            throw new \LogicException('FailedEmailManager has not been initialized.');
        }

        return $this->failedEmailManager;
    }

    public function getPlatformName(): string
    {
        return str_replace(' ', '-', $this->parameterBag->get('platform_name'));
    }
}
