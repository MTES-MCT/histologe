<?php

namespace App\Service\Mailer\Mail\Signalement;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger, $this->urlGenerator, $this->entityManager);
    }

    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();

        return [
            'signalement' => $signalement,
            'link' => $this->generateLink(
                'show_uploaded_file', [
                    'folder' => '_up',
                    'filename' => $notificationMail->getParams()['filename'] ?? self::FILE_404,
                    'uuid' => $signalement->getUuid(),
                ]
            ),
        ];
    }
}
