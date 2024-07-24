<?php

namespace App\FormHandler;

use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContactFormHandler
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function handle(
        string $nom,
        string $email,
        string $message,
        string $organisme,
        string $objet
    ) {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CONTACT_FORM,
                to: $this->parameterBag->get('contact_email'),
                fromEmail: $email,
                fromFullname: $nom,
                message: nl2br(strip_tags($message)),
                params: [
                    'organisme' => $organisme,
                    'objet' => $objet,
                ]
            )
        );
    }
}
