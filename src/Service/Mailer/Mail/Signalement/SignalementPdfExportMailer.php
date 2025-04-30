<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementPdfExportMailer extends AbstractNotificationMailer
{
    public const FILE_404 = 'blank.pdf';
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_PDF_EXPORT;
    protected ?string $mailerSubject = 'Voici l\'export pdf du signalement !';
    protected ?string $mailerButtonText = 'Afficher le PDF';
    protected ?string $mailerTemplate = 'signalement_pdf_export';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $filename = $notificationMail->getParams()['filename'] ?? self::FILE_404;
        // TODO : changer le secret
        $token = hash_hmac('sha256', 'suivi_signalement_ext_file_view'.$signalement->getUuid().$filename, '$secret');
        $link = $this->generateLink(
            'show_uploaded_file', [
                'folder' => '_up',
                'filename' => $filename,
                'uuid' => $signalement->getUuid(),
                't' => $token, // TODO : seulement si depuis front
            ]
        );

        return [
            'signalement_reference' => $signalement->getReference(),
            'link' => $link,
        ];
    }
}
