<?php

namespace App\FormHandler;

use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContactFormHandler
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag,
        private SignalementRepository $signalementRepository,
        private SuiviFactory $suiviFactory,
        private SuiviManager $suiviManager,
        private UserManager $userManager,
        private LoggerInterface $logger,
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
