<?php

namespace App\Service\Mailer\Mail;

use App\Entity\Territory;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

abstract class AbstractNotificationMailer implements NotificationMailerInterface
{
    protected ?NotificationMailerType $mailerType = null;
    protected ?string $mailerSubject = null;
    protected ?string $mailerButtonText = null;
    protected ?string $mailerTemplate = null;

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
    ) {
    }

    public function send(NotificationMail $notification): bool
    {
        $territory = $notification->getTerritory();
        $this->setMailerSubjectWithParams($notification->getParams());
        $params = [
            'template' => $this->mailerTemplate,
            'subject' => $this->mailerSubject,
            'btntext' => $this->mailerButtonText,
            'url' => $this->parameterBag->get('host_url'),
        ];

        $params = array_merge($params, $notification->getParams());

        $message = $this->renderMailContentWithParams($params, $territory ?? null);

        $territoryName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')
            ->transliterate(
                (!empty($territory) && null !== $territory->getName())
                    ? $territory->getName()
                    : 'ALERTE'
            );

        foreach ($notification->getEmails() as $email) {
            $email && $message->addTo($email);
        }

        $message->from(
            new Address(
                $this->parameterBag->get('notifications_email'),
                'HISTOLOGE - '.mb_strtoupper($territoryName)
            )
        );

        if (!empty($params['attach'])) {
            $message->attachFromPath($params['attach']);
        }
        try {
            $this->mailer->send($message);

            return true;
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error(sprintf('[%s] %s', $notification->getType(), $exception->getMessage()));
        }

        return false;
    }

    public function supports(NotificationMailerType $type): bool
    {
        return $this->mailerType === $type;
    }

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
                'HISTOLOGE '.mb_strtoupper((!empty($territory) && null !== $territory->getName())
                    ? $territory->getName()
                    : 'ALERTE').' - '.$this->mailerSubject
            );
    }

    public function setMailerSubjectWithParams(?array $params = null): void
    {
    }
}
