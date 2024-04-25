<?php

namespace App\Service\Mailer\Mail;

use App\Entity\Territory;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
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
    protected ?string $tagHeader = null;
    protected array $mailerParams = [];

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
        $params = [
            'template' => $this->mailerTemplate,
            'subject' => $this->mailerSubject,
            'btntext' => $this->mailerButtonText,
            'url' => $this->parameterBag->get('host_url'),
        ];

        $params = array_merge($params, $notificationMail->getParams(), $this->mailerParams);
        $message = $this->renderMailContentWithParams($params, $territory ?? null);

        if (null !== $this->tagHeader) {
            $message->getHeaders()->add(new TagHeader($this->tagHeader));
        }

        $territoryName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')
            ->transliterate(
                (!empty($territory) && null !== $territory->getName())
                    ? $territory->getName()
                    : 'ALERTE'
            );

        foreach ($notificationMail->getEmails() as $email) {
            try {
                $email && $message->addTo($email);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('[%s] %s', $notificationMail->getType()->name, $e->getMessage()));
            }
        }

        $message->from(
            new Address(
                $this->parameterBag->get('notifications_email'),
                mb_strtoupper($this->parameterBag->get('platform_name').' - '.$territoryName)
            )
        );

        if (!empty($params['attach'])) {
            $message->attachFromPath($params['attach']);
        }
        if (!empty($params['attachContent'])) {
            $message->attach($params['attachContent']['content'], $params['attachContent']['filename']);
        }
        try {
            $this->mailer->send($message);

            return true;
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('[%s] %s', $notificationMail->getType()->name, $exception->getMessage()));
        }

        return false;
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

    public function generateLink(string $route, array $params): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate($route, $params);
    }

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

    abstract public function getMailerParamsFromNotification(NotificationMail $notificationMail): array;

    private function renderMailContentWithParams(
        array $params,
        Territory|null $territory
    ): NotificationEmail {
        $config['territory'] = $territory;
        $notification = new NotificationEmail();
        $notification->markAsPublic();

        return $notification->htmlTemplate('emails/'.$this->mailerTemplate.'.html.twig')
            ->context(array_merge($params, $config))
            ->subject(
                mb_strtoupper($this->parameterBag->get('platform_name'))
                .' '
                .mb_strtoupper((!empty($territory) && null !== $territory->getName()) ? $territory->getName() : 'ALERTE')
                .' - '.
                str_replace('%param.platform_name%', $this->parameterBag->get('platform_name'), $this->mailerSubject)
            );
    }
}
