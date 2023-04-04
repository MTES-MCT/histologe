<?php

namespace App\Service\Mailer\Mail\Suivi;

use App\Service\Mailer\Mail\AbstractNotificationMailer;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class SuiviNewCommentBackMailer extends AbstractNotificationMailer
{
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_NEW_COMMENT_BACK;
    protected ?string $mailerButtonText = 'Accéder au signalement';
    protected ?string $mailerTemplate = 'nouveau_suivi_signalement_back_email';

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger
    ) {
        parent::__construct($this->mailer, $this->parameterBag, $this->logger);
    }

    public function setMailerSubjectWithParams(?array $params = null)
    {
        $this->mailerSubject = sprintf('Nouveau suivi sur le signalement #%s', $params['ref_signalement']);
    }
}
