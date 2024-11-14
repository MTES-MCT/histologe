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

class SignalementListExportMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_LIST_EXPORT;
    protected ?string $mailerSubject = 'Votre export de la liste des signalements';
    protected ?string $mailerButtonText = 'Afficher l\'export';
    protected ?string $mailerTemplate = 'signalement_list_export';

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
        return [
            'link' => $this->generateLink(
                'show_file', [
                    'uuid' => $notificationMail->getParams()['file_uuid'],
                ]
            ),
        ];
    }
}
