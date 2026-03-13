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

class SignalementEditMailOccupantMailer extends AbstractNotificationMailer
{
    private const int SIGNATURE_VALIDITY_DURATION = 86400; // 24h
    protected ?NotificationMailerType $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_EDIT_MAIL_OCCUPANT;
    protected ?string $mailerSubject = 'Modification de votre adresse e-mail';
    protected ?string $mailerButtonText = 'Confirmer ma nouvelle adresse';
    protected ?string $mailerTemplate = 'signalement_edit_mail_occupant';
    protected ?string $tagHeader = 'Usager Edit MailOccupant';

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
     */
    public function getMailerParamsFromNotification(NotificationMail $notificationMail): array
    {
        $signalement = $notificationMail->getSignalement();
        $url = $this->urlGenerator->generate(
            'front_signalement_confirm_edit_mail_occupant',
            ['code' => $signalement->getCodeSuivi()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $lien_btn = $this->urlSigner->sign($url, self::SIGNATURE_VALIDITY_DURATION);

        return [
            'reference' => $signalement->getReference(),
            'lien_signalement' => $this->urlGenerator->generate(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'lien_btn' => $lien_btn,
        ];
    }
}
