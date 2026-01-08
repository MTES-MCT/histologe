<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DownloadExportMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_DOWNLOAD_EXPORT;
    protected ?string $mailerSubject = null;
    protected ?string $mailerButtonText = null;
    protected ?string $mailerTemplate = 'download_export';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        protected UrlSignerInterface $urlSigner,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    /**
     * @return array<mixed>
     *
     * @throws \DateMalformedStringException
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $this->mailerSubject = $notificationMail->getParams()['message'];
        $this->mailerButtonText = $notificationMail->getParams()['button_text'];
        $url = $this->generateLink('show_file', ['uuid' => $notificationMail->getParams()['file_uuid']]);
        $expiration = (new \DateTime())->modify('+1 month');

        return ['link' => $this->urlSigner->sign($url, $expiration)];
    }
}
